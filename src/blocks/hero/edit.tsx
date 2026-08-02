import { useBlockProps, useInnerBlocksProps } from "@wordpress/block-editor";

import "./editor.scss";

import MediaInput from "../../components/MediaInput";
import bindFields from "../../utils/bindFields";

const TEMPLATE = [
  ["jm/paragraph", { className: "jm__label" }],
  ["jm/heading", { level: "1", lock_level: true, className: "jm-hero__para" }],
  ["jm/paragraph", { className: "jm-hero__heading" }],
];

const ALLOWED_BLOCKS = ["jm/heading", "jm/paragraph"];

export default function Edit({ attributes, setAttributes }) {
  const { imageUrl } = attributes;

  const bind = bindFields(attributes, setAttributes);

  const blockProps = useBlockProps({
    className: "jm-section jm-hero",
  });

  const innerBlocksProps = useInnerBlocksProps(
    {
      className: "jm-hero__text",
    },
    {
      allowedBlocks: ALLOWED_BLOCKS,
      template: TEMPLATE,
      templateLock: false,
    },
  );

  const imageProps = {
    className: "jm-hero__img",
    style: {
      "--background-image": imageUrl ? `url("${imageUrl}")` : "none",
    } as React.CSSProperties,
  };

  return (
    <section {...blockProps}>
      <div {...innerBlocksProps} />
      <div {...imageProps}>
        <MediaInput {...bind.media("imageId", "imageUrl")} />
      </div>
    </section>
  );
}
