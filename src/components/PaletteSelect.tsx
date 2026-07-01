import { SelectControl } from "@wordpress/components";

type PaletteOption = {
  label: string;
  value: string;
};
import { PanelBody } from "@wordpress/components";

type Props = {
  value: string;
  set: (value: string) => void;
  options?: PaletteOption[];
};

const defaultOptions: PaletteOption[] = [
  { label: "Default", value: "jm-palette--default" },
  { label: "Inverse", value: "jm-palette--inverse" },
  { label: "Secondary", value: "jm-palette--secondary" },
];

const PaletteSelect = ({ value, set, options = defaultOptions }: Props) => {
  return (
    <SelectControl
      label="Palette"
      value={value}
      options={options}
      onChange={set}
    />
  );
};

export default PaletteSelect;
