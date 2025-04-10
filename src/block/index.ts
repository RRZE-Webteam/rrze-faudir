/**
 * The brain of the block – Connecting Edit.tsx and the Editor Styles
 *
 * Note: This is a dynamic block. The Frontend is handled within includes/BlockRegister.php
 */
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import save from './save';
import metadata from './block.json';

registerBlockType( metadata.name as any, {
	edit: Edit,
	save
} as any );
