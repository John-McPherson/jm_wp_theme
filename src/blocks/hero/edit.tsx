import { __ } from "@wordpress/i18n";

import { useBlockProps } from "@wordpress/block-editor";

import "./editor.scss";
import TextInput from "../../components/TextInput";

import MediaInput from "../../components/MediaInput";
import bindFields from "../../utils/bindFields";

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
  const { imageId, imageUrl } = attributes;

  const bind = bindFields(attributes, setAttributes);

  const imgProps = {
    className: "hero-img",
    style: {
      "--background-image": imageUrl ? `url(${imageUrl})` : "none",
    } as React.CSSProperties,
  };
  const blockProps = useBlockProps();
  return (
    <div {...blockProps}>
      <section className="jm-section jm-hero">
        <div className="hero-text">
          <TextInput {...bind.text("heading")} tagName="h1" />
          <TextInput {...bind.text("text")} />
        </div>
        <div {...imgProps}>
          <MediaInput {...bind.media("imageId", "imageUrl")} />
        </div>
      </section>
    </div>
  );
}
