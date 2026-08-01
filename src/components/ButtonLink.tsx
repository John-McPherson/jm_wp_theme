import LinkToolBar from "./LinkToolBar";
import TextInput from "./TextInput";

type ButtonProps = {
  text: {
    value: string;
    set: (value: string) => void;
  };
  link: {
    value: { url: string; opensInNewTab: boolean };
    set: (value: { url: string; opensInNewTab: boolean }) => void;
    reset: () => void;
  };
  className?: string;
};

const ButtonLink = ({ text, link, className }: ButtonProps) => {
  return (
    <>
      <LinkToolBar {...link} />
      <TextInput {...text} className={className || "jm-button"} />
    </>
  );
};

export default ButtonLink;
