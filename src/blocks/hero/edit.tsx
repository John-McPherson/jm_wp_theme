import { __ } from "@wordpress/i18n";

import {
  useBlockProps,
  RichText,
  MediaUpload,
  MediaUploadCheck,
} from "@wordpress/block-editor";

import { Button } from "@wordpress/components";

import { useSelect } from "@wordpress/data";

import "./editor.scss";

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
  const { heading, text, imageId, imageUrl } = attributes;
  console.log("imageId", imageId);

  const banner = (
    <section className="jm-section jm-hero">
      <div className="hero-text">
        <RichText
          tagName="h1"
          allowFormats={[]}
          value={heading}
          onChange={(value: string) => setAttributes({ heading: value })}
          placeholder={__("heading to go here", "jm-theme")}
        ></RichText>
        <RichText
          tagName="p"
          allowFormats={[]}
          value={text}
          onChange={(value: string) => setAttributes({ text: value })}
          placeholder={__("Enter text here", "jm-theme")}
        ></RichText>
      </div>
      <div
        className="hero-img"
        style={
          { "--background-image": `url(${imageUrl})` } as React.CSSProperties
        }
      >
        <MediaUploadCheck>
          <MediaUpload
            onSelect={(image: { id: number; url: string }) => {
              console.log("image", image);
              setAttributes({
                imageId: image.id,
                imageUrl: image.url,
              });
            }}
            type="image"
            allowedTypes={["image"]}
            render={({ open }) => (
              <Button variant="primary" onClick={open}>
                {__("Upload Image", "jm-theme")}
              </Button>
            )}
          />
        </MediaUploadCheck>
      </div>
    </section>
  );

  return <div {...useBlockProps()}>{banner}</div>;
}
