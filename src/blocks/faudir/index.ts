/**
 * The brain of the block – Connecting Edit.tsx and the Editor Styles
 *
 * Note: This is a dynamic block. The Frontend is handled within includes/BlockRegister.php
 */
import { registerBlockType, type BlockConfiguration } from "@wordpress/blocks";
import Edit from "./edit";
import save from "./save";
import deprecated from "./deprecated";
import metadata from "./block.json";
import type { FaudirBlockAttributes } from "./types";

registerBlockType<FaudirBlockAttributes>(
  metadata as BlockConfiguration<FaudirBlockAttributes>,
  {
    edit: Edit,
    save,
    deprecated,
  },
);
