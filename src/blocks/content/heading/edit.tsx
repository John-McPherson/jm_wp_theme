import { __ } from "@wordpress/i18n";
import { useBlockProps } from "@wordpress/block-editor";

import TextInput from "../../../components/TextInput";
import bindFields from "../../../utils/bindFields";
import Sidebar from "../../../components/Sidebar";
import SelectInput from "../../../components/SelectInput";

const HEADING_TAGS = {
  "1": "h1",
  "2": "h2",
  "3": "h3",
  "4": "h4",
  "5": "h5",
  "6": "h6",
} as const;

type HeadingLevel = keyof typeof HEADING_TAGS;

const VARIANT_CLASSES = {
  default: "",
  hero: "jm-hero__text-heading",
};

export default function Edit({ attributes, setAttributes, context }) {
  const { className } = attributes;
  const blockProps = useBlockProps();
  const bind = bindFields(attributes, setAttributes);

  const { level, lock_level } = attributes;

  const variant = context["jm/variant"] ?? "default";

  const classes = [className, VARIANT_CLASSES[variant]]
    .filter(Boolean)
    .join(" ");

  const sideBar = (
    <Sidebar>
      <Sidebar.Section title={__("Heading level", "jm-theme")}>
        <SelectInput
          {...({
            value: String(level),
            type: "heading_level",
            set: (value: string) =>
              setAttributes({ level: value as HeadingLevel }),
          } as any)}
        />
      </Sidebar.Section>
    </Sidebar>
  );

  return (
    <>
      {lock_level && sideBar}
      <div {...blockProps}>
        <TextInput
          {...bind.text("text")}
          tagName={HEADING_TAGS[level]}
          className={classes}
        />
      </div>
    </>
  );
}
