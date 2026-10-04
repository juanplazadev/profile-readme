<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Frame;

use JuanPlaza\Profile\Svg\Color;
use JuanPlaza\Profile\Svg\Component\Markup;
use JuanPlaza\Profile\Svg\Font\FontSubsetter;
use JuanPlaza\Profile\Svg\Geometry as G;

/**
 * Wraps a slice body in its piece of the console frame.
 *
 * Every slice draws the same side rails; only the first slice has the top edge and title
 * bar, only the last one closes the frame. Half slices (project cards) and segments (link
 * buttons) sit side by side in one README row and only draw the rails on their outer edges.
 */
final readonly class Frames
{
    private const string BASE_CSS = <<<'CSS'
        text{font-family:'JBM',ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:15px}
        .dim{fill:#8b949e}.cy{fill:#00d9ff}.fg{fill:#c9d1d9}.gr{fill:#3fb950}.wh{fill:#f0fbff}
        @keyframes fadein{from{opacity:0;transform:translateX(-6px)}to{opacity:1;transform:none}}
        @keyframes blink{0%,49%{opacity:1}50%,100%{opacity:0}}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.35}}
        .ln{animation:fadein .35s ease-out both}
        .cursor{animation:blink 1.05s step-end infinite}
        .dot{animation:pulse 2s ease-in-out infinite}
        CSS;

    private const string REDUCED_MOTION = '@media (prefers-reduced-motion:reduce){*{animation:none!important}}';

    public function __construct(private FontSubsetter $fonts) {}

    /** @param list<int> $weights */
    public function full(
        int $h,
        string $body,
        string $title,
        string $desc,
        string $text,
        bool $top = false,
        bool $bottom = false,
        string $css = '',
        string $defs = '',
        array $weights = [400, 700],
    ): string {
        $this->assertHeight($h);
        $fl = G::FL;
        $fr = G::FR;
        $y0 = $top ? G::M : 0;
        $y1 = $bottom ? $h - G::M : $h;

        $rails = "M$fl {$y0}V{$y1}M$fr {$y0}V$y1";
        $glow = 'M' . $fl . ' ' . ($top ? $y0 : -40) . 'V' . ($bottom ? $y1 : $h + 40)
            . 'M' . $fr . ' ' . ($top ? $y0 : -40) . 'V' . ($bottom ? $y1 : $h + 40);
        $corners = '';
        if ($top) {
            $rails .= "M$fl {$y0}H$fr";
            $glow .= "M$fl {$y0}H$fr";
            $corners .= '<path d="M' . ($fl - 7) . ' ' . ($y0 + 18) . 'V' . ($y0 - 7) . 'H' . ($fl + 18) . '"/>'
                . '<path d="M' . ($fr - 18) . ' ' . ($y0 - 7) . 'H' . ($fr + 7) . 'V' . ($y0 + 18) . '"/>';
        }
        if ($bottom) {
            $rails .= "M$fl {$y1}H$fr";
            $glow .= "M$fl {$y1}H$fr";
            $corners .= '<path d="M' . ($fl - 7) . ' ' . ($y1 - 18) . 'V' . ($y1 + 7) . 'H' . ($fl + 18) . '"/>'
                . '<path d="M' . ($fr - 18) . ' ' . ($y1 + 7) . 'H' . ($fr + 7) . 'V' . ($y1 - 18) . '"/>';
        }
        $cy = Color::Cyan->value;
        $fw = $fr - $fl;
        $fh = $y1 - $y0;
        $grid = $this->grid();

        return $this->document(G::W, $h, $title, $desc, $text . Markup::visible($body), $weights, $css, $grid . "\n" . $this->filters(G::W, $h) . ($defs ? "\n$defs" : ''), <<<SVG
            <path d="$glow" fill="none" stroke="$cy" stroke-width="3" opacity=".55" filter="url(#glow)"/>
            <rect x="$fl" y="$y0" width="$fw" height="$fh" fill="#03040a"/>
            <rect x="$fl" y="$y0" width="$fw" height="$fh" fill="url(#grid)"/>
            $body
            <path d="$rails" fill="none" stroke="$cy" stroke-width="1.2"/>
            <g fill="none" stroke="$cy" stroke-width="2">$corners</g>
            SVG);
    }

    /** Left or right half of a full-width row; only its outer side has a rail. */
    public function half(int $h, Side $side, string $body, string $title, string $desc, string $text): string
    {
        $this->assertHeight($h);
        $rx = $side === Side::Left ? G::FL : G::HW - G::M;
        [$bx0, $bx1] = $side === Side::Left ? [G::FL, G::HW] : [0, G::HW - G::M];
        $bw = $bx1 - $bx0;
        $cy = Color::Cyan->value;
        $glowEnd = $h + 40;

        return $this->document(G::HW, $h, $title, $desc, $text . Markup::visible($body), [400, 700], '', $this->grid() . "\n" . $this->filters(G::HW, $h), <<<SVG
            <path d="M$rx -40V$glowEnd" fill="none" stroke="$cy" stroke-width="3" opacity=".55" filter="url(#glow)"/>
            <rect x="$bx0" y="0" width="$bw" height="$h" fill="#03040a"/>
            <rect x="$bx0" y="0" width="$bw" height="$h" fill="url(#grid)"/>
            $body
            <path d="M$rx 0V$h" fill="none" stroke="$cy" stroke-width="1.2"/>
            SVG);
    }

    /**
     * One of $count equal segments of a full-width row (the link buttons).
     * $x0 is the segment's position inside the row, so the grid and rails stay aligned.
     */
    public function segment(int $h, int $index, int $count, string $body, string $title, string $desc, string $text): string
    {
        $seg = intdiv(G::W, $count);
        $x0 = $seg * $index;
        $first = $index === 0;
        $last = $index === $count - 1;
        $bg0 = $first ? G::FL - $x0 : 0;
        $bg1 = $last ? G::FR - $x0 : $seg;
        $bw = $bg1 - $bg0;
        $cy = Color::Cyan->value;

        $rails = ($first ? 'M' . (G::FL - $x0) . " 0V$h" : '') . ($last ? 'M' . (G::FR - $x0) . " 0V$h" : '');
        $glow = ($first ? 'M' . (G::FL - $x0) . ' -40V' . ($h + 40) : '') . ($last ? 'M' . (G::FR - $x0) . ' -40V' . ($h + 40) : '');
        $glowPath = $glow ? "<path d=\"$glow\" fill=\"none\" stroke=\"$cy\" stroke-width=\"3\" opacity=\".55\" filter=\"url(#glow)\"/>\n" : '';
        $railPath = $rails ? "\n<path d=\"$rails\" fill=\"none\" stroke=\"$cy\" stroke-width=\"1.2\"/>" : '';
        $gridX = ((-$x0 % 40) + 40) % 40;   // Python's modulo: always non-negative

        return $this->document($seg, $h, $title, $desc, $text . Markup::visible($body), [400, 700], '', $this->grid($gridX) . "\n" . $this->filters($seg, $h),
            $glowPath . <<<SVG
            <rect x="$bg0" y="0" width="$bw" height="$h" fill="#03040a"/>
            <rect x="$bg0" y="0" width="$bw" height="$h" fill="url(#grid)"/>
            $body
            SVG . $railPath);
    }

    /** @param list<int> $weights */
    private function document(int $w, int $h, string $title, string $desc, string $text, array $weights, string $css, string $defs, string $content): string
    {
        $t = Markup::esc($title);
        $d = Markup::esc($desc);
        $faces = $this->fonts->fontFaces($text, $weights);
        $base = self::BASE_CSS;
        $motion = self::REDUCED_MOTION;
        $css = $css === '' ? '' : "$css\n";
        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h" role="img" aria-labelledby="t d">
            <title id="t">$t</title>
            <desc id="d">$d</desc>
            <style>
            $faces
            $base
            $css$motion
            </style>
            <defs>
            $defs
            </defs>
            $content
            </svg>

            SVG;
    }

    /**
     * The glow region is in user space: a half or segment glows a single vertical rail, whose
     * bounding box has zero width, so a percentage region would be empty and draw nothing.
     */
    private function filters(int $w, int $h): string
    {
        return '<filter id="glow" filterUnits="userSpaceOnUse" x="-40" y="-80" width="' . ($w + 80) . '" height="' . ($h + 160) . '">'
            . '<feGaussianBlur stdDeviation="6"/></filter>' . "\n"
            . '<filter id="g" x="-20%" y="-60%" width="140%" height="220%"><feGaussianBlur stdDeviation="4"/></filter>';
    }

    private function grid(int $x = 0): string
    {
        $xAttr = $x ? " x=\"$x\"" : '';
        return '<pattern id="grid"' . $xAttr . ' width="40" height="40" patternUnits="userSpaceOnUse"><path d="M40 0H0V40" fill="none" stroke="'
            . Color::Cyan->value . '" stroke-opacity=".06"/></pattern>';
    }

    private function assertHeight(int $h): void
    {
        if ($h % 40 !== 0) {
            throw new \LogicException("Slice height must be a multiple of 40, got $h");
        }
    }
}
