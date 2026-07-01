const bindFields = (attributes: any, setAttributes: any) => {
  return {
    text: (key: string) => ({
      value: attributes[key],
      set: (value: string) => {
        setAttributes({ [key]: value });
      },
    }),

    media: (keyId: string, keyUrl?: string) => ({
      value: attributes[keyId],
      set: (media: { id: number; url: string }) => {
        setAttributes({
          [keyId]: media.id,
          ...(keyUrl ? { [keyUrl]: media.url } : {}),
        });
      },
    }),
    select: (key: string) => ({
      value: attributes[key],
      set: (value: string) => {
        setAttributes({ [key]: value });
      },
    }),
    link: (key: string) => ({
      value: attributes[key],
      set: (value: { url: string; opensInNewTab: boolean }) => {
        setAttributes({ [key]: value });
      },
      reset: () => {
        setAttributes({ [key]: { url: "", opensInNewTab: false } });
      },
    }),
  };
};

export default bindFields;
