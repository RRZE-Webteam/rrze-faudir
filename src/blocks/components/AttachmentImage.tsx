import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';

type MediaSizeVariant = {
  source_url?: string;
  width?: number;
  height?: number;
};

type MediaDetails = {
  width?: number;
  height?: number;
  sizes?: Record<string, MediaSizeVariant | undefined>;
};

type MediaItem = {
  id: number;
  source_url?: string;
  alt_text?: string;
  media_details?: MediaDetails;
};

export type AttachmentImageProps = {
  imageId?: number | null;
  size?: string;
};

/**
 * AttachmentImage
 *
 * This component is used to display an image from the media library.
 * It's meant as a JS companion to the PHP function `wp_get_attachment_image()`.
 *
 * @link https://www.briancoords.com/getting-wordpress-media-library-images-in-javascript/
 *
 * @param {AttachmentImageProps} props
 * @returns {JSX.Element | null} React JSX
 */
interface CoreMediaStore {
  getMedia?: (id: number) => MediaItem | null;
}

export default function AttachmentImage({ imageId, size = 'full' }: AttachmentImageProps){

  const image = useSelect((selectFn) => {
    if (!imageId) {
      return null;
    }
    const coreStore = selectFn('core') as CoreMediaStore;
    return coreStore?.getMedia ? coreStore.getMedia(imageId) : null;
  }, [imageId]);

  const imageAttributes = useMemo(() => {
    if (!image || !image.source_url) {
      return null;
    }

    const baseAttributes = {
      src: image.source_url,
      alt: image.alt_text ?? '',
      className: `attachment-${size} size-${size}`,
      width: image.media_details?.width,
      height: image.media_details?.height,
    };

    const sizeVariant = image.media_details?.sizes?.[size];
    if (sizeVariant?.source_url) {
      return {
        ...baseAttributes,
        src: sizeVariant.source_url,
        width: sizeVariant.width ?? baseAttributes.width,
        height: sizeVariant.height ?? baseAttributes.height,
      };
    }

    return baseAttributes;
  }, [image, size]);

  if (!imageAttributes) {
    return null;
  }

  return <img {...imageAttributes} />;
}
