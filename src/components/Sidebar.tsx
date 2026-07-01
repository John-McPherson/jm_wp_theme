import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody } from "@wordpress/components";
import { ReactNode } from "react";

type SidebarProps = {
  children: ReactNode;
};

type SectionProps = {
  title: string;
  children: ReactNode;
  initialOpen?: boolean;
};

function Sidebar({ children }: SidebarProps) {
  return <InspectorControls>{children}</InspectorControls>;
}

function Section({ title, children, initialOpen = true }: SectionProps) {
  return (
    <PanelBody title={title} initialOpen={initialOpen}>
      {children}
    </PanelBody>
  );
}

Sidebar.Section = Section;

export default Sidebar;
