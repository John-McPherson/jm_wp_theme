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
