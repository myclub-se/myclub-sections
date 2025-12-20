import {useEffect, useState} from '@wordpress/element';
import {InspectorControls, useBlockProps} from '@wordpress/block-editor';
import {PanelBody, PanelRow, SelectControl, Spinner} from '@wordpress/components';
import ServerSideRender from "@wordpress/server-side-render";
import {__} from "@wordpress/i18n";
import {getMyClubSections} from "../shared/edit-functions";

/**
 * The edit function required to handle the news component. Adds a post chooser to the settings and updates the block
 * which is rendered by the backend.
 *
 * @return {Element} Element to render.
 */
export default function Edit({attributes, setAttributes}) {
    const [posts, setPosts] = useState([]);
    const selectPostLabel = {
        label: __('Select a section', 'myclub-sections'),
        value: ''
    };

    useEffect(() => {
        // Get all myclub section posts.
        getMyClubSections(setPosts, selectPostLabel);
    }, []);

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Content settings', 'myclub-sections')}>
                    <PanelRow>
                        {posts.length ?
                            <SelectControl
                                label={__('Section', 'myclub-sections')}
                                value={attributes.post_id}
                                options={posts}
                                onChange={(value) => {
                                    setAttributes({post_id: value});
                                }}
                            /> : <Spinner/>}
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <div {...useBlockProps()}>
                <ServerSideRender block="myclub-sections/news" attributes={attributes}/>
            </div>
        </>
    );
}
