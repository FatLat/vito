import { Fragment, useMemo } from 'react';
import { cn } from '@/lib/utils';

type AnsiStyle = {
  color?: string;
  bold?: boolean;
  dim?: boolean;
};

type AnsiSegment = {
  text: string;
  style: AnsiStyle;
};

const FOREGROUND_COLORS: Record<number, string> = {
  30: 'text-zinc-500',
  31: 'text-red-600 dark:text-red-400',
  32: 'text-green-600 dark:text-green-400',
  33: 'text-yellow-600 dark:text-yellow-400',
  34: 'text-blue-600 dark:text-blue-400',
  35: 'text-fuchsia-600 dark:text-fuchsia-400',
  36: 'text-cyan-600 dark:text-cyan-400',
  37: 'text-zinc-700 dark:text-zinc-200',
  90: 'text-muted-foreground',
  91: 'text-red-500 dark:text-red-300',
  92: 'text-green-500 dark:text-green-300',
  93: 'text-yellow-500 dark:text-yellow-300',
  94: 'text-blue-500 dark:text-blue-300',
  95: 'text-fuchsia-500 dark:text-fuchsia-300',
  96: 'text-cyan-500 dark:text-cyan-300',
  97: 'text-foreground',
};

const ESCAPE_SEQUENCE = new RegExp(`${String.fromCharCode(27)}\\[([0-9;?]*)([A-Za-z])`, 'g');

function applyCodes(style: AnsiStyle, params: string): AnsiStyle {
  const codes = params === '' ? [0] : params.split(';').map((code) => Number(code));
  let next = { ...style };

  for (const code of codes) {
    if (code === 0) {
      next = {};
    } else if (code === 1) {
      next.bold = true;
    } else if (code === 2) {
      next.dim = true;
    } else if (code === 22) {
      next.bold = false;
      next.dim = false;
    } else if (code === 39) {
      next.color = undefined;
    } else if (FOREGROUND_COLORS[code]) {
      next.color = FOREGROUND_COLORS[code];
    }
  }

  return next;
}

export function parseAnsi(input: string): AnsiSegment[] {
  const segments: AnsiSegment[] = [];
  let style: AnsiStyle = {};
  let lastIndex = 0;

  for (const match of input.matchAll(ESCAPE_SEQUENCE)) {
    const index = match.index ?? 0;
    if (index > lastIndex) {
      segments.push({ text: input.slice(lastIndex, index), style });
    }
    if (match[2] === 'm') {
      style = applyCodes(style, match[1]);
    }
    lastIndex = index + match[0].length;
  }

  if (lastIndex < input.length) {
    segments.push({ text: input.slice(lastIndex), style });
  }

  return segments;
}

export default function AnsiText({ text }: { text: string }) {
  const segments = useMemo(() => parseAnsi(text), [text]);

  return (
    <>
      {segments.map((segment, index) =>
        segment.style.color || segment.style.bold || segment.style.dim ? (
          <span key={index} className={cn(segment.style.color, segment.style.bold && 'font-bold', segment.style.dim && 'opacity-70')}>
            {segment.text}
          </span>
        ) : (
          <Fragment key={index}>{segment.text}</Fragment>
        ),
      )}
    </>
  );
}
