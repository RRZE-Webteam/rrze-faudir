export interface EditProps {
  attributes: {
    orgid: string;
    identifier: string;
  };
  setAttributes: (attributes: Partial<EditProps["attributes"]>) => void;
  clientId: string;
  blockProps: any;
}
