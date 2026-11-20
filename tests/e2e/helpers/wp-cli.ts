import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const execFileAsync = promisify( execFile );

type CliResult = {
	stdout: string;
	stderr: string;
};

export async function runWpCli( args: string[] ): Promise< CliResult > {
	const { stdout, stderr } = await execFileAsync(
		'npx',
		[ 'wp-env', 'run', 'cli', 'wp', ...args ],
		{
			env: process.env,
			maxBuffer: 1024 * 1024,
		}
	);

	return {
		stdout: stdout.trim(),
		stderr: stderr.trim(),
	};
}

export async function getThemeMod( name: string ): Promise< string | null > {
	const code = [
		`$value = get_theme_mod(${ JSON.stringify( name ) }, null);`,
		'if ($value !== null && $value !== false && $value !== "") {',
		'echo (string) $value;',
		'}',
	].join( ' ' );

	const { stdout } = await runWpCli( [ 'eval', code ] );

	return stdout === '' ? null : stdout;
}

export async function setThemeMod(
	name: string,
	value: string | number
): Promise< void > {
	const encodedName = JSON.stringify( name );
	const encodedValue = JSON.stringify( value );

	await runWpCli( [
		'eval',
		`set_theme_mod(${ encodedName }, ${ encodedValue });`,
	] );
}

export async function removeThemeMod( name: string ): Promise< void > {
	await runWpCli( [
		'eval',
		`remove_theme_mod(${ JSON.stringify( name ) });`,
	] );
}

export async function importTestLogo(): Promise< number > {
	const { stdout: fixturePath } = await runWpCli( [
		'eval',
		'echo get_theme_file_path("tests/e2e/fixtures/logo.png");',
	] );

	if ( ! fixturePath ) {
		throw new Error( 'Could not resolve the logo fixture path.' );
	}

	const { stdout } = await runWpCli( [
		'media',
		'import',
		fixturePath,
		'--porcelain',
	] );

	const attachmentId = Number(
		stdout
			.split( '\n' )
			.map( ( line ) => line.trim() )
			.filter( Boolean )
			.at( -1 )
	);

	if ( ! Number.isInteger( attachmentId ) || attachmentId <= 0 ) {
		throw new Error( `Could not determine imported logo ID: ${ stdout }` );
	}

	return attachmentId;
}

export async function deleteAttachment(
	attachmentId: number
): Promise< void > {
	await runWpCli( [ 'post', 'delete', String( attachmentId ), '--force' ] );
}

export async function getOption( name: string ): Promise< string > {
	const { stdout } = await runWpCli( [ 'option', 'get', name ] );

	return stdout;
}

export async function updateOption(
	name: string,
	value: string
): Promise< void > {
	await runWpCli( [ 'option', 'update', name, value ] );
}

type NavigationLink = {
	label: string;
	url: string;
	id?: number;
};

function buildNavigationContent( links: NavigationLink[] ): string {
	return links
		.map( ( link ) => {
			return `<!-- wp:navigation-link ${ JSON.stringify( link ) } /-->`;
		} )
		.join( '' );
}

export async function createNavigationRecord(
	links: NavigationLink[]
): Promise< number > {
	const content = buildNavigationContent( links );

	const { stdout } = await runWpCli( [
		'post',
		'create',
		'--post_type=wp_navigation',
		'--post_status=publish',
		'--post_title=Navigation E2E',
		`--post_content=${ content }`,
		'--porcelain',
	] );

	return Number( stdout );
}

export async function updateNavigationRecord(
	navigationId: number,
	links: NavigationLink[]
): Promise< void > {
	await runWpCli( [
		'post',
		'update',
		String( navigationId ),
		`--post_content=${ buildNavigationContent( links ) }`,
	] );
}

export async function createNavigationTestPage(
	navigationId: number
): Promise< number > {
	const content = `<!-- wp:jmc/navigation ${ JSON.stringify( {
		navigationId,
	} ) } /-->`;

	const { stdout } = await runWpCli( [
		'post',
		'create',
		'--post_type=page',
		'--post_status=publish',
		'--post_title=Navigation E2E page',
		`--post_content=${ content }`,
		'--porcelain',
	] );

	return Number( stdout );
}

type PostFields = {
	type: string;
	title: string;
	content: string;
	parent?: number;
	excerpt?: string;
	template?: string;
};

export async function createPost( {
	type,
	title,
	content,
	parent,
	excerpt,
	template,
}: PostFields ): Promise< number > {
	const { stdout } = await runWpCli( [
		'post',
		'create',
		`--post_type=${ type }`,
		'--post_status=publish',
		`--post_title=${ title }`,
		`--post_content=${ content }`,
		...( parent ? [ `--post_parent=${ parent }` ] : [] ),
		...( excerpt ? [ `--post_excerpt=${ excerpt }` ] : [] ),
		...( template ? [ `--page_template=${ template }` ] : [] ),
		'--porcelain',
	] );

	const postId = Number( stdout );

	if ( ! Number.isInteger( postId ) || postId <= 0 ) {
		throw new Error( `Could not determine created post ID: ${ stdout }` );
	}

	return postId;
}

export async function deletePost( postId: number ): Promise< void > {
	await runWpCli( [ 'post', 'delete', String( postId ), '--force' ] );
}

export async function getPermalink( postId: number ): Promise< string > {
	const { stdout } = await runWpCli( [
		'eval',
		`echo get_permalink(${ Number( postId ) });`,
	] );

	return stdout;
}

/**
 * Run PHP with a JSON value decoded into `$value`, passed as base64 so the
 * value never needs shell or PHP string escaping.
 *
 * @param value Value available to the code as `$value`.
 * @param code  PHP code to run.
 */
async function evalWithValue(
	value: unknown,
	code: string
): Promise< string > {
	const encoded = Buffer.from( JSON.stringify( value ) ).toString( 'base64' );
	const { stdout } = await runWpCli( [
		'eval',
		`$value = json_decode(base64_decode('${ encoded }'), true); ${ code }`,
	] );

	return stdout;
}

/**
 * Read options so tests can restore them afterwards, including options that
 * do not exist yet (`wp option get` fails for those).
 *
 * @param names Option names.
 * @return Each option's value as JSON, or `null` when it is not set.
 */
export async function snapshotOptions(
	names: string[]
): Promise< Record< string, unknown > > {
	const stdout = await evalWithValue(
		names,
		'$sentinel = new stdClass(); $result = []; foreach ($value as $name) { $option = get_option($name, $sentinel); $result[$name] = $option === $sentinel ? null : $option; } echo wp_json_encode($result);'
	);

	return JSON.parse( stdout ) as Record< string, unknown >;
}

/**
 * Restore options read by snapshotOptions(), deleting those that were unset.
 *
 * @param snapshot Values from snapshotOptions().
 */
export async function restoreOptions(
	snapshot: Record< string, unknown >
): Promise< void > {
	await evalWithValue(
		snapshot,
		'foreach ($value as $name => $option) { if (null === $option) { delete_option($name); } else { update_option($name, $option); } }'
	);
}

/**
 * Set options to JSON-compatible values, such as arrays.
 *
 * @param options Values keyed by option name.
 */
export async function setOptions(
	options: Record< string, unknown >
): Promise< void > {
	await evalWithValue(
		options,
		'foreach ($value as $name => $option) { update_option($name, $option); }'
	);
}

/**
 * Read the content of the active theme's customised template part, if any.
 *
 * @param slug Template part slug, such as `header`.
 * @return The saved customisation, or `null` while the theme file is used.
 */
export async function snapshotTemplatePart(
	slug: string
): Promise< { id: number; content: string } | null > {
	const stdout = await evalWithValue(
		slug,
		'$part = get_block_template(get_stylesheet() . "//" . $value, "wp_template_part"); echo wp_json_encode($part && $part->wp_id ? ["id" => $part->wp_id, "content" => $part->content] : null);'
	);

	return JSON.parse( stdout ) as { id: number; content: string } | null;
}

/**
 * Undo Site Editor changes to a template part: put a saved customisation back,
 * or delete the customisation so the theme file is used again.
 *
 * @param slug     Template part slug, such as `header`.
 * @param snapshot Value from snapshotTemplatePart().
 */
export async function restoreTemplatePart(
	slug: string,
	snapshot: { id: number; content: string } | null
): Promise< void > {
	await evalWithValue(
		{ slug, snapshot },
		'$part = get_block_template(get_stylesheet() . "//" . $value["slug"], "wp_template_part"); if ($value["snapshot"]) { wp_update_post(["ID" => $value["snapshot"]["id"], "post_content" => wp_slash($value["snapshot"]["content"])]); } elseif ($part && $part->wp_id) { wp_delete_post($part->wp_id, true); }'
	);
}
