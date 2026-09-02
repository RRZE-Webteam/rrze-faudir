import { __ } from "@wordpress/i18n";
import { MediaReplaceFlow } from "@wordpress/block-editor";
interface MediaMetadata {
  alt: string;
  author: string;
  authorLink: string;
  caption: string;
  compat: {
    item: string;
    meta: string;
  };
  context: string;
  date: Date;
  dateFormatted: string;
  description: string;
  editLink: string;
  filename: string;
  filesizeHumanReadable: string;
  filesizeInBytes: number;
  height: number;
  icon: string;
  id: number;
  link: string;
  menuOrder: number;
  meta: boolean;
  mime: string;
  modified: Date;
  name: string;
  nonces: {
    update: string;
    delete: string;
    edit: string;
  };
  orientation: string;
  sizes: {
    thumbnail: {
      height: number;
      orientation: string;
      url: string;
      width: number;
    };
    full: {
      height: number;
      orientation: string;
      url: string;
      width: number;
    };
    large: {
      height: number;
      orientation: string;
      url: string;
      width: number;
    };
    medium: {
      height: number;
      orientation: string;
      url: string;
      width: number;
    };
  };
  status: string;
  subtype: string;
  title: string;
  type: "image" | "video";
  uploadedTo: number;
  uploadedToLink: string;
  uploadedToTitle: string;
  url: string;
  width: number;
}

interface ImageSelectorProps {
  mediaId?: number;
  mediaURL?: string;
  mediaHeight?: number;
  mediaWidth?: number;
  setAttributes: (attributes: {
    imageURL?: string;
    imageId?: number;
    imageWidth?: number;
    imageHeight?: number;
  }) => void;
}

export default function ImageSelector({
  mediaId = 0,
  mediaURL = "",
  mediaHeight = 0,
  mediaWidth = 0,
  setAttributes,
}: ImageSelectorProps) {
  const onSelectMedia = (newMedia: MediaMetadata) => {
    const mediaAttributes = attributesFromMedia(newMedia);

    setAttributes({
      imageURL: mediaAttributes.url,
      imageId: mediaAttributes.id ?? 0,
      imageWidth: mediaAttributes.width ?? 0,
      imageHeight: mediaAttributes.height ?? 0,
    });
  };

  const onResetMedia = () => clearMedia();
  const onErrorMedia = () => clearMedia();

  const clearMedia = () => {
    setAttributes({
      imageURL: "",
      imageWidth: 0,
      imageHeight: 0,
      imageId: 0,
    });
  };

  const attributesFromMedia = (media: MediaMetadata) => {
    if (!media || !media.url) {
      return {
        url: "",
      };
    }

    return {
      url: media.url,
      id: media.id,
      alt: media?.alt,
      height: media.height,
      width: media.width,
    };
  };

  return (
    <MediaReplaceFlow
      mediaId={mediaId}
      mediaURL={mediaURL}
      allowedTypes={["image"]}
      accept="image/*"
      onSelect={onSelectMedia}
      onToggleFeaturedImage={() => {}}
      name={!mediaURL ? __("Add media") : __("Replace")}
      onReset={onResetMedia}
      onError={onErrorMedia}
    />
  );
}
