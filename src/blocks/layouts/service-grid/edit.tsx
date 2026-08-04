import { __ } from "@wordpress/i18n";

import { useBlockProps, useInnerBlocksProps } from "@wordpress/block-editor";

import "./editor.scss";

import bindFields from "../../../utils/bindFields";

import Sidebar from "../../../components/Sidebar";
import SelectInput from "../../../components/SelectInput";

const TEMPLATE = [
  [
    "jm/column",
    {},
    [
      ["jm/paragraph", { variant: "label" }],
      ["jm/heading", { level: "2" }],
    ],
  ],
  [
    "jm/column",
    {},
    [
      ["jm/service-card"],
      ["jm/service-card"],
      ["jm/service-card"],
      ["jm/service-card"],
    ],
  ],
];

const VARIANT_CLASSES = {
  default: "",
  inverse: "jm-palette--inverse",
  secondary: "jm-palette--secondary",
};

const ALLOWED_BLOCKS = ["jm/column"];

export default function Edit({ attributes, setAttributes }) {
  const bind = bindFields(attributes, setAttributes);

  const { palette, order } = attributes;
  const blockProps = useBlockProps({
    className: ["jm-section", "jm-service-grid", VARIANT_CLASSES[palette]]
      .filter(Boolean)
      .join(" "),
  });

  const innerBlocksProps = useInnerBlocksProps(
    {
      className: ["jm-section__container", VARIANT_CLASSES[order]],
    },
    {
      allowedBlocks: ALLOWED_BLOCKS,
      template: TEMPLATE,
      templateLock: true,
    },
  );

  return (
    <>
      <Sidebar>
        <Sidebar.Section title={__("Palette Settings", "jm-theme")}>
          <SelectInput {...bind.select("palette")} type="palette" />
        </Sidebar.Section>
      </Sidebar>

      <section {...blockProps}>
        <div {...innerBlocksProps} />
      </section>
    </>
  );
}
