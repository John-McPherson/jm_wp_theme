import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import save from './save';

registerBlockType('amc/hero', {
  edit: Edit,
  save,
});