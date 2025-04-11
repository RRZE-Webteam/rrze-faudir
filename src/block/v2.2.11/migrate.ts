import { AttributesV1 } from "./attributes";

const migrate = (attributes: AttributesV1): AttributesV1 => {
  return {
    ...attributes,
    initialSetup: false,
  };
};

export default migrate;