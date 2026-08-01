import { SelectControl } from "@wordpress/components";

type PaletteOption = {
  label: string;
  value: string;
};

type Props = {
  value: string;
  set: (value: string) => void;
  type?: string;
};

const options: Record<string, PaletteOption[]> = {
  palette: [
    { label: "Default", value: "jm-palette--default" },
    { label: "Inverse", value: "jm-palette--inverse" },
    { label: "Secondary", value: "jm-palette--secondary" },
  ],
  order: [
    { label: "Image Left", value: "jm-text-with-image--image-left" },
    { label: "Image Right", value: "jm-text-with-image--image-right" },
  ],
};

const SelectInput = ({ value, set, type = "palette" }: Props) => {
  return (
    <SelectControl
      label={type.toUpperCase()}
      value={value}
      options={options[type]}
      onChange={set}
    />
  );
};

export default SelectInput;
