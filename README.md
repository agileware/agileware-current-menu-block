# Current Menu Block by Agileware

Provides a "Current Menu Block" for the WordPress Block Editor

## Installation

Extract the plugin archive in your WordPress plugins directory and activate

## Usage

1. From the Widgets block editor, add a "Current Menu Block" to your desired widget area.
2. Select a menu to display using the "Cog" toolbar dropdown for the widget.

After updating the widgets, sidebars or other areas where the block has been added will now display a contextually sensitive selection of the menu you've selected, based on the currently queried Post or other database object. If there is an exact match, that will be used; otherwise, for Pages and custom post types that support the "Parent" model, the block will check ancestors to select a menu level.