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
    { label: "Default", value: "default" },
    { label: "Inverse", value: "inverse" },
    { label: "Secondary", value: "secondary" },
  ],
  order: [
    { label: "Image Left", value: "left" },
    { label: "Image Right", value: "right" },
  ],
  heading_level: [
    { label: "H1", value: "1" },
    { label: "H2", value: "2" },
    { label: "H3", value: "3" },
    { label: "H4", value: "4" },
    { label: "H5", value: "5" },
    { label: "H6", value: "6" },
  ],
  style: [
    { label: "Primary", value: "primary" },
    { label: "Secondary", value: "secondary" },
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
