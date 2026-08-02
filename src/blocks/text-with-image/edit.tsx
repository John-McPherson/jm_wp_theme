import { __ } from "@wordpress/i18n";

import { useBlockProps } from "@wordpress/block-editor";

import "./editor.scss";

import TextInput from "../../components/TextInput";
import bindFields from "../../utils/bindFields";
import ButtonLink from "../../components/ButtonLink";
import MediaInput from "../../components/MediaInput";
import Sidebar from "../../components/Sidebar";
import SelectInput from "../../components/SelectInput";

export default function Edit({ attributes, setAttributes }) {
  const bind = bindFields(attributes, setAttributes);
  const { imageUrl, palette, order } = attributes;
  const blockProps = useBlockProps();

  const sectionClassName = ["jm-section", "jm-text-with-image", palette, order];

  return (
    <>
      <Sidebar>
        <Sidebar.Section title={__("Palette Settings", "jm-theme")}>
          <SelectInput {...bind.select("palette")} type="palette" />
        </Sidebar.Section>
        <Sidebar.Section title={__("Order Settings", "jm-theme")}>
          <SelectInput {...bind.select("order")} type="order" />
        </Sidebar.Section>
      </Sidebar>

      <div {...blockProps}>
        <section className={sectionClassName.join(" ")}>
          <div className="jm-section__container">
            <div className="jm-section__column">
              <TextInput
                {...bind.text("label")}
                tagName="p"
                className="label"
              />

              <div className="jm-text-with-image__text">
                <TextInput {...bind.text("heading")} tagName="h2" />
                <TextInput {...bind.text("text")} />
              </div>

              <ButtonLink
                text={bind.text("linkText")}
                link={bind.link("link")}
              />
            </div>

            <div className="jm-section__column">
              <div className="jm-image jm-text-with-image__image">
                {imageUrl && <img src={imageUrl} />}
                <MediaInput {...bind.media("imageId", "imageUrl")} />
              </div>
            </div>
          </div>
        </section>
      </div>
    </>
  );
}
