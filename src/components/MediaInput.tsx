import { MediaUpload, MediaUploadCheck } from "@wordpress/block-editor";
import { Button } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

type MediaInputTypes = "image" | "video" | "audio";

type MediaInputProps = {
  value: string | number | null;
  type?: MediaInputTypes;
  onSelect: (media: { id: number; url: string }) => void;
  className?: string;
};

const labels = {
  image: {
    select: __("Select image", "jm-theme"),
    replace: __("Replace image", "jm-theme"),
  },
  video: {
    select: __("Select video", "jm-theme"),
    replace: __("Replace video", "jm-theme"),
  },
  audio: {
    select: __("Select audio", "jm-theme"),
    replace: __("Replace audio", "jm-theme"),
  },
};

const MediaInput = ({
  value,
  type = "image",
  onSelect,
  className = "",
}: MediaInputProps) => {
  return (
    <MediaUploadCheck>
      <MediaUpload
        onSelect={onSelect}
        type={type}
        allowedTypes={[type]}
        className={className}
        render={({ open }) => (
          <Button variant="primary" onClick={open}>
            {value ? labels[type].replace : labels[type].select}
          </Button>
        )}
      />
    </MediaUploadCheck>
  );
};

export default MediaInput;
