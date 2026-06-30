const bindFields = (attributes: any, setAttributes: any) => {
  return {
    text: (key: string) => ({
      value: attributes[key],
      onChange: (value: string) => {
        setAttributes({ [key]: value });
      },
    }),

    media: (keyId: string, keyUrl?: string) => ({
      value: attributes[keyId],
      onSelect: (media: { id: number; url: string }) => {
        setAttributes({
          [keyId]: media.id,
          ...(keyUrl ? { [keyUrl]: media.url } : {}),
        });
      },
    }),
  };
};

export default bindFields;
