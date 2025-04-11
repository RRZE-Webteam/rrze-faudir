import {BlockDeprecation, BlockInstance} from "@wordpress/blocks";

import migrateV2_2_11 from "./v2.2.11/migrate";
import {attributes as attributesV2_2_11, AttributesV1} from "./v2.2.11/attributes";

const deprecated: BlockDeprecation<AttributesV1>[] = [
  {
    attributes: attributesV2_2_11,
    save: null,
    migrate: migrateV2_2_11,
    isEligible(attributes: Record<string, any>, innerBlocks: BlockInstance[]): boolean {
      return true;
    }
  }
]

export default deprecated;