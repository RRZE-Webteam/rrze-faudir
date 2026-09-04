declare module "@wordpress/block-editor" {
  import type { ComponentType, HTMLAttributes, ReactNode } from "react";

  interface EditorWrapperProps {
    children?: ReactNode;
  }

  export interface MediaReplaceFlowProps {
    mediaId: number;
    mediaURL: string;
    allowedTypes?: string[];
    accept?: string;
    onSelect: (media: any) => void;
    onError: (error: string) => void;
    onToggleFeaturedImage?: (value: boolean) => void;
    useFeaturedImage?: boolean;
    name: string;
    onReset?: () => void;
  }

  export interface LinkControlProps {
    onChange: (change: any) => void;
    value?: {
      url?: string;
      opensInNewTab?: boolean;
    };
    onRemove: (any: any) => void;
    forceIsEditingLink?: any;
  }

  export interface __experimentalBlockVariationPicker {
    variations: any[];
    onSelect?: (variation: any) => void;
    selectedVariation?: any;
    label?: string;
  }

  export const MediaReplaceFlow: ComponentType<MediaReplaceFlowProps>;
  export const BlockControls: ComponentType<EditorWrapperProps>;
  export const InspectorControls: ComponentType<EditorWrapperProps>;
  export function useBlockProps(): HTMLAttributes<HTMLDivElement>;
  export const __experimentalLinkControl: ComponentType<LinkControlProps>;
  export const __experimentalBlockVariationPicker: ComponentType<__experimentalBlockVariationPicker>;
}
