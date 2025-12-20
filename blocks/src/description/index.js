/**
 * Registers the description block.
 */
import {registerBlockType} from '@wordpress/blocks';
import './style.scss';

import Edit from './edit';
import metadata from './block.json';

/**
 * Register the description block and add translations for the titel and description.
 */
registerBlockType(metadata.name, {
    /**
     * @see ./edit.js
     */
    edit: Edit,
});
