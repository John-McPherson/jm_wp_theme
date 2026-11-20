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

/**
 * The permalink of a post.
 *
 * @param postId Post ID.
 */
export async function getPermalink( postId: number ): Promise< string > {
	const { stdout } = await runWpCli( [
		'eval',
		`echo get_permalink(${ Number( postId ) });`,
	] );

	return stdout;
}

/**
 * Run PHP in WordPress with `wp eval`, passing data as JSON in `$args`.
 *
 * @param code PHP to run; `$args` holds the decoded `args`.
 * @param args Data for the code, encoded as JSON.
 */
async function evalWithArgs( code: string, args: unknown ): Promise< string > {
	// A single-quoted PHP string, so `$` and `\` in the JSON stay literal.
	const json = JSON.stringify( args )
		.replace( /\\/g, '\\\\' )
		.replace( /'/g, "\\'" );

	const { stdout } = await runWpCli( [
		'eval',
		`$args = json_decode( '${ json }', true ); ${ code }`,
	] );

	return stdout;
}

/** Option values keyed by name; `null` for an option that is not stored. */
export type OptionBackup = Record< string, unknown >;

/**
 * Read options as stored, so they can be put back with `setOptions()`.
 *
 * @param names Option names.
 */
export async function getOptions( names: string[] ): Promise< OptionBackup > {
	const stdout = await evalWithArgs(
		[
			'$values = [];',
			'foreach ( $args as $name ) {',
			'$value = get_option( $name, null );',
			'$values[ $name ] = false === $value ? null : $value;',
			'}',
			'echo wp_json_encode( $values );',
		].join( ' ' ),
		names
	);

	return JSON.parse( stdout.split( '\n' ).at( -1 ) ?? '{}' );
}

/**
 * Store each option, or delete it when its value is `null`.
 *
 * @param values Option values keyed by name.
 */
export async function setOptions( values: OptionBackup ): Promise< void > {
	await evalWithArgs(
		[
			'foreach ( $args as $name => $value ) {',
			'null === $value ? delete_option( $name ) : update_option( $name, $value );',
			'}',
		].join( ' ' ),
		values
	);
}

type PatternPageFields = {
	title: string;
	patterns: string[];
	slug?: string;
	parent?: number;
};

/**
 * Publish a page built from the theme's registered block patterns, in order.
 *
 * @param fields          Page fields.
 * @param fields.title    Page title.
 * @param fields.patterns Pattern names, such as `jmc/home-page`.
 * @param fields.slug     Page slug; WordPress makes it unique if it is taken.
 * @param fields.parent   Parent page ID.
 */
export async function createPageFromPatterns( {
	title,
	patterns,
	slug = '',
	parent = 0,
}: PatternPageFields ): Promise< number > {
	const stdout = await evalWithArgs(
		[
			'$registry = WP_Block_Patterns_Registry::get_instance();',
			'$content = "";',
			'foreach ( $args["patterns"] as $name ) {',
			'$pattern = $registry->get_registered( $name );',
			'if ( ! $pattern ) { fwrite( STDERR, "Unknown pattern: $name" ); exit( 1 ); }',
			'$content .= $pattern["content"];',
			'}',
			'$id = wp_insert_post( [',
			'"post_type" => "page", "post_status" => "publish",',
			'"post_title" => $args["title"], "post_name" => $args["slug"],',
			'"post_parent" => $args["parent"], "post_content" => wp_slash( $content ),',
			'], true );',
			'if ( is_wp_error( $id ) ) { fwrite( STDERR, $id->get_error_message() ); exit( 1 ); }',
			'echo $id;',
		].join( ' ' ),
		{ title, patterns, slug, parent }
	);

	const pageId = Number( stdout.split( '\n' ).at( -1 ) );

	if ( ! Number.isInteger( pageId ) || pageId <= 0 ) {
		throw new Error( `Could not determine created page ID: ${ stdout }` );
	}

	return pageId;
}

/**
 * Save a customised copy of one of the theme's template parts, as the Site
 * Editor does, with text in the theme file replaced. Delete the returned
 * post to go back to the theme file.
 *
 * Throws if the part is already customised, so a test never overwrites or
 * deletes someone's own edit.
 *
 * @param slug         Template part slug, such as `header`.
 * @param area         Template part area, such as `header` or `footer`.
 * @param replacements Text to replace in the theme file, keyed by the text to find.
 */
export async function customizeTemplatePart(
	slug: string,
	area: string,
	replacements: Record< string, string >
): Promise< number > {
	const stdout = await evalWithArgs(
		[
			'$theme = get_stylesheet();',
			'$existing = get_block_template( $theme . "//" . $args["slug"], "wp_template_part" );',
			'if ( $existing && "custom" === $existing->source ) {',
			'fwrite( STDERR, "Template part is already customised: " . $args["slug"] ); exit( 1 );',
			'}',
			'$content = file_get_contents( get_theme_file_path( "parts/" . $args["slug"] . ".html" ) );',
			'foreach ( $args["replacements"] as $search => $replace ) {',
			'if ( false === strpos( $content, $search ) ) { fwrite( STDERR, "Not found: $search" ); exit( 1 ); }',
			'$content = str_replace( $search, $replace, $content );',
			'}',
			'$id = wp_insert_post( [',
			'"post_type" => "wp_template_part", "post_status" => "publish",',
			'"post_name" => $args["slug"], "post_title" => $args["slug"],',
			'"post_content" => wp_slash( $content ),',
			'], true );',
			'if ( is_wp_error( $id ) ) { fwrite( STDERR, $id->get_error_message() ); exit( 1 ); }',
			'wp_set_object_terms( $id, $theme, "wp_theme" );',
			'wp_set_object_terms( $id, $args["area"], "wp_template_part_area" );',
			'echo $id;',
		].join( ' ' ),
		{ slug, area, replacements }
	);

	const partId = Number( stdout.split( '\n' ).at( -1 ) );

	if ( ! Number.isInteger( partId ) || partId <= 0 ) {
		throw new Error(
			`Could not determine template part ID for ${ slug }: ${ stdout }`
		);
	}

	return partId;
}

/** A customised template part's post ID and content. */
export type TemplatePartSnapshot = { id: number; content: string } | null;

/**
 * Read the content of the active theme's customised template part, if any.
 *
 * @param slug Template part slug, such as `header`.
 * @return The saved customisation, or `null` while the theme file is used.
 */
export async function snapshotTemplatePart(
	slug: string
): Promise< TemplatePartSnapshot > {
	const stdout = await evalWithArgs(
		[
			'$part = get_block_template( get_stylesheet() . "//" . $args, "wp_template_part" );',
			'echo wp_json_encode( $part && $part->wp_id ? [ "id" => $part->wp_id, "content" => $part->content ] : null );',
		].join( ' ' ),
		slug
	);

	return JSON.parse(
		stdout.split( '\n' ).at( -1 ) ?? 'null'
	) as TemplatePartSnapshot;
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
	snapshot: TemplatePartSnapshot
): Promise< void > {
	await evalWithArgs(
		[
			'$part = get_block_template( get_stylesheet() . "//" . $args["slug"], "wp_template_part" );',
			'if ( $args["snapshot"] ) {',
			'wp_update_post( [ "ID" => $args["snapshot"]["id"], "post_content" => wp_slash( $args["snapshot"]["content"] ) ] );',
			'} elseif ( $part && $part->wp_id ) {',
			'wp_delete_post( $part->wp_id, true );',
			'}',
		].join( ' ' ),
		{ slug, snapshot }
	);
}
