import {registerBlockType} from '@wordpress/blocks';
import {useBlockProps, BlockControls, withColors} from '@wordpress/block-editor';
import {ToolbarGroup, ToolbarDropdownMenu, MenuGroup, MenuItemsChoice} from '@wordpress/components';
import {cogAlt as configIcon} from '@wordpress/icons'
import {Fragment, useState} from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';

function CurrentMenu( { attributes, setAttributes } ) {
	const [ selectedMenu, setSelectedMenu ] = useState ( parseInt( attributes.navigationMenuId ) )
	const blockProps = useBlockProps();

	const menuSelector = <ToolbarDropdownMenu
		icon={ configIcon }
		label="Select Menu">
		{ ( { onClose }) => (
			<Fragment>
				<MenuGroup label="Select Menu">
					<MenuItemsChoice
						choices={ window.currentMenuBlockData.options.map( ( { name: label, term_id: value } ) => ( {
							label, value
						} ) ) }
						value={ selectedMenu }
						onSelect={ ( value ) => {
							setAttributes( { navigationMenuId: value } );
							setSelectedMenu( value );
							onClose();
						} }
					/>
				</MenuGroup>
			</Fragment>
		) }
	</ToolbarDropdownMenu>

	const LoadingResponsePlaceholder = () => <p>Waiting for server response</p>;

	return (
		<Fragment>
			<BlockControls>
				<ToolbarGroup>{ menuSelector }</ToolbarGroup>
			</BlockControls>
			<div { ...blockProps }>
				<ServerSideRender block="agileware/current-menu"
								  attributes={ { navigationMenuId: attributes.navigationMenuId } }
								  LoadingResponsePlaceholder={ LoadingResponsePlaceholder }/>
			</div>
		</Fragment>
	);
}

registerBlockType( 'agileware/current-menu', {
	edit: withColors( { linkColor: 'color' }, { currentItemColor: 'color' } )( CurrentMenu )
} );
