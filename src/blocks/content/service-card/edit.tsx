import { useBlockProps, useInnerBlocksProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";

import bindFields from "../../../utils/bindFields";
import LinkToolBar from "../../../components/LinkToolBar";
import Sidebar from "../../../components/Sidebar";
import SelectInput from "../../../components/SelectInput";

const TEMPLATE = [
  ["jm/heading", { level: "2", lock_level: true }],
  ["jm/paragraph"],
];

const ALLOWED_BLOCKS = ["jm/heading", "jm/paragraph"];

export default function Edit({ attributes, setAttributes }) {
  const blockProps = useBlockProps({ className: "jm-service-card" });

  const bind = bindFields(attributes, setAttributes);

  const innerBlocksProps = useInnerBlocksProps(
    {},
    {
      allowedBlocks: ALLOWED_BLOCKS,
      template: TEMPLATE,
      templateLock: true,
    },
  );
  return (
    <>
      <Sidebar>
        <Sidebar.Section title={__("Select Icon", "jm-theme")}>
          <SelectInput {...bind.select("icon")} type="icons" />
        </Sidebar.Section>
      </Sidebar>
      <div {...blockProps}>
        <LinkToolBar {...bind.link("link")} />
        <div {...innerBlocksProps} />
      </div>
    </>
  );
}
