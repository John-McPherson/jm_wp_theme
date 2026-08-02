import { __ } from "@wordpress/i18n";

import { LinkControl, BlockControls } from "@wordpress/block-editor";
import { Popover, ToolbarButton } from "@wordpress/components";
import { link as linkIcon } from "@wordpress/icons";

import { useRef, useState } from "react";

type LinkToolBarProps = {
  value: { url: string; opensInNewTab: boolean };
  set: (value: { url: string; opensInNewTab: boolean }) => void;
  reset: () => void;
};

const LinkToolBar = ({ value, set, reset }: LinkToolBarProps) => {
  const [isLinkOpen, setIsLinkOpen] = useState(false);

  const buttonRef = useRef<HTMLDivElement | null>(null);

  return (
    <>
      {isLinkOpen && buttonRef.current && (
        <Popover
          anchor={buttonRef.current}
          onClose={() => setIsLinkOpen(false)}
        >
          <div style={{ minWidth: 300, padding: 12 }}>
            <LinkControl
              value={value}
              onChange={set}
              onRemove={() => {
                reset();
                setIsLinkOpen(false);
              }}
            />
          </div>
        </Popover>
      )}

      <BlockControls>
        <div ref={buttonRef}>
          <ToolbarButton
            icon={linkIcon}
            label={__("Edit link", "jm-theme")}
            isPressed={isLinkOpen}
            onClick={() => setIsLinkOpen((open) => !open)}
          />
        </div>
      </BlockControls>
    </>
  );
};

export default LinkToolBar;
