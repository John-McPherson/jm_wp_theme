import { SelectControl } from "@wordpress/components";

type Option = {
  label: string;
  value: string;
};

type Props = {
  value: string;
  set: (value: string) => void;
  type: keyof typeof options;
};

const options: Record<string, Option[]> = {
  palette: [
    { label: "Default", value: "jm-palette--default" },
    { label: "Inverse", value: "jm-palette--inverse" },
    { label: "Secondary", value: "jm-palette--secondary" },
  ],
  order: [
    { label: "Image Left", value: "jm-text-with-image--image-left" },
    { label: "Image Right", value: "jm-text-with-image--image-right" },
  ],
  heading_level: [
    { label: "H1", value: "1" },
    { label: "H2", value: "2" },
    { label: "H3", value: "3" },
    { label: "H4", value: "4" },
    { label: "H5", value: "5" },
    { label: "H6", value: "6" },
  ],
} as const;

const SelectInput = ({ value, set, type = "palette" }: Props) => {
  return (
    <SelectControl
      label={type.toUpperCase()}
      value={String(value)}
      options={options[type]}
      onChange={set}
    />
  );
};

export default SelectInput;
