import fs from "fs";
import path from "path";

console.log("🚀 Building full design system tokens...");

const themePath = path.resolve(process.cwd(), "theme.json");
const outPath = path.resolve(process.cwd(), "src/scss/tokens/_generated.scss");

if (!fs.existsSync(themePath)) {
  console.error("❌ theme.json not found");
  process.exit(1);
}

const theme = JSON.parse(fs.readFileSync(themePath, "utf8"));

/* =========================================================
   HELPERS
========================================================= */

function mapSlug(slug) {
  const aliases = {
    s: "sm",
    m: "md",
    l: "lg",
  };

  return aliases[slug] ?? slug;
}

/**
 * Converts theme.json slugs to the format WordPress uses
 * for preset CSS variables.
 *
 * Examples:
 * h1  -> h-1
 * d3  -> d-3
 * p4  -> p-4
 * 2xl -> 2-xl
 * 4xl -> 4-xl
 */
function wpSlug(slug) {
  return slug
    .replace(/([a-zA-Z])(\d)/g, "$1-$2")
    .replace(/(\d)([a-zA-Z])/g, "$1-$2");
}

function pxComment(value) {
  if (typeof value !== "string") return "";

  const rem = value.match(/^(-?\d*\.?\d+)rem$/);

  if (rem) {
    const px = parseFloat(rem[1]) * 16;
    return ` /* ${Number.isInteger(px) ? px : px.toFixed(2)}px */`;
  }

  const px = value.match(/^(-?\d*\.?\d+)px$/);

  if (px) {
    return ` /* ${px[1]}px */`;
  }

  return "";
}

/* =========================================================
   TOKEN CONFIG
========================================================= */

const TOKEN_CONFIG = {
  colors: {
    label: "COLORS",
    source: () => theme.settings?.color?.palette ?? [],
    cssVar: (t) => `--jm-color-${mapSlug(t.slug)}`,
    value: (t) => `var(--wp--preset--color--${wpSlug(t.slug)})`,
  },

  spacing: {
    label: "SPACING",
    source: () => theme.settings?.spacing?.spacingSizes ?? [],
    cssVar: (t) => `--jm-spacing-${mapSlug(t.slug)}`,
    value: (t) => `var(--wp--preset--spacing--${wpSlug(t.slug)})`,
    comment: (t) => pxComment(t.size),
  },

  radius: {
    label: "RADIUS",
    source: () => theme.settings?.border?.radiusSizes ?? [],
    cssVar: (t) => `--jm-radius-${mapSlug(t.slug)}`,
    value: (t) => `var(--wp--preset--border-radius--${wpSlug(t.slug)})`,
    comment: (t) => pxComment(t.size),
  },

  fontFamilies: {
    label: "FONT FAMILIES",
    source: () => theme.settings?.typography?.fontFamilies ?? [],
    cssVar: (t) => `--jm-font-${t.slug}`,
    value: (t) => `"${t.fontFamily}"`,
  },

  typography: {
    label: "TYPOGRAPHY",
    source: () => theme.settings?.typography?.fontSizes ?? [],
    cssVar: (t) => `--jm-${mapSlug(t.slug)}`,
    value: (t) => `var(--wp--preset--font-size--${wpSlug(t.slug)})`,
    comment: (t) => pxComment(t.size),
  },
};

/* =========================================================
   BUILD
========================================================= */

let scss = `/* AUTO-GENERATED FILE — DO NOT EDIT */

@layer base {
  :root {
`;

function add(line = "") {
  scss += `${line}\n`;
}

for (const cfg of Object.values(TOKEN_CONFIG)) {
  const tokens = cfg.source();

  if (!tokens.length) continue;

  add("");
  add("    /* =========================================================");
  add(`     ${cfg.label}`);
  add("    ========================================================= */");

  for (const token of tokens) {
    const comment = cfg.comment ? cfg.comment(token) : "";

    add(`    ${cfg.cssVar(token)}: ${cfg.value(token)};${comment}`);
  }
}

scss += `
  }
}
`;

/* =========================================================
   WRITE
========================================================= */

fs.mkdirSync(path.dirname(outPath), { recursive: true });
fs.writeFileSync(outPath, scss);

console.log(`✅ Generated ${outPath}`);
