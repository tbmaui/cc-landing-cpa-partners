#!/usr/bin/env node
/**
 * Validates the theme's block markup.
 *
 * WordPress fails quietly on malformed block markup — a pattern with a broken
 * attribute payload or an unbalanced tag renders as an "unexpected content"
 * notice, or silently drops. Neither shows up as a PHP error, so this runs the
 * two checks that actually catch it:
 *
 *   1. Every block comment's JSON attribute payload parses.
 *   2. Every non-self-closing block opens and closes in the right order.
 *
 * Usage:  node tools/validate-blocks.js
 * Exits non-zero on failure so it can gate a commit hook or CI step.
 */
'use strict';

const fs = require( 'fs' );
const path = require( 'path' );

const ROOT = path.resolve( __dirname, '..', 'wordpress' );
const DIRS = [ 'patterns', 'templates', 'parts' ];

// Matches an opening, closing or self-closing block comment.
const BLOCK = /<!--\s+(\/)?wp:([a-z0-9/-]+)(\s+\{[\s\S]*?\})?\s*(\/)?-->/g;

function collect() {
	const requested = process.argv.slice( 2 );

	if ( requested.length ) {
		return requested.map( ( name ) => path.relative( ROOT, path.resolve( ROOT, name ) ) );
	}

	const files = [];

	for ( const dir of DIRS ) {
		const full = path.join( ROOT, dir );
		if ( ! fs.existsSync( full ) ) {
			continue;
		}
		for ( const name of fs.readdirSync( full ) ) {
			if ( /\.(php|html)$/.test( name ) ) {
				files.push( path.join( dir, name ) );
			}
		}
	}

	return files;
}

function check( relative ) {
	const src = fs.readFileSync( path.join( ROOT, relative ), 'utf8' );
	const errors = [];
	const stack = [];
	let match;

	BLOCK.lastIndex = 0;

	while ( ( match = BLOCK.exec( src ) ) ) {
		const [ , closing, name, attrs, selfClosing ] = match;

		if ( attrs ) {
			try {
				JSON.parse( attrs.trim() );
			} catch ( e ) {
				errors.push( `wp:${ name } has invalid attribute JSON — ${ e.message }` );
			}
		}

		if ( selfClosing ) {
			continue;
		}

		if ( closing ) {
			const open = stack.pop();
			if ( open !== name ) {
				errors.push( `closed /wp:${ name } but the open block was wp:${ open || '(none)' }` );
				break;
			}
		} else {
			stack.push( name );
		}
	}

	if ( stack.length ) {
		errors.push( `never closed: ${ stack.map( ( n ) => 'wp:' + n ).join( ', ' ) }` );
	}

	return errors;
}

let failed = 0;

for ( const file of collect() ) {
	const errors = check( file );

	if ( errors.length ) {
		failed++;
		console.error( `FAIL  ${ file }` );
		errors.forEach( ( e ) => console.error( `      ${ e }` ) );
	} else {
		console.log( `ok    ${ file }` );
	}
}

if ( failed ) {
	console.error( `\n${ failed } file(s) with invalid block markup.` );
	process.exit( 1 );
}

console.log( '\nAll block markup valid.' );
