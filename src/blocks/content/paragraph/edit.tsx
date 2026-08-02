import { __ } from "@wordpress/i18n";
import { useBlockProps } from "@wordpress/block-editor";

import "./editor.scss";

import TextInput from "../../../components/TextInput";
import bindFields from "../../../utils/bindFields";

const VARIANT_CLASSES = {
  default: "",
  hero: "jm-hero__text-para",
};

export default function Edit({ attributes, setAttributes, context }) {
  const { className } = attributes;
  const blockProps = useBlockProps();
  const bind = bindFields(attributes, setAttributes);

  const variant = context["jm/variant"] ?? "default";

  const classes = [className, VARIANT_CLASSES[variant]]
    .filter(Boolean)
    .join(" ");

  return (
    <>
      <div {...blockProps}>
        <TextInput {...bind.text("text")} tagName="p" className={classes} />
      </div>
    </>
  );
}
