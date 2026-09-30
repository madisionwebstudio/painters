# Placeholders to fill before launch

Search the project for `[` + `PLACEHOLDER`, `CONFIRM`, `YOUR-DOMAIN` and `[EMAIL]` to find every spot.

## Business facts
| # | Placeholder | Where | Notes |
|---|---|---|---|
| 1 | **Owner name** `[OWNER NAME]` | Footer (all pages) | |
| 2 | **Email** `[EMAIL]` | Footer (all pages), `mailer.php` → `TO_EMAIL` | Leads go here |
| 3 | **Business hours** | Footer (all pages) | Must match Google Business Profile exactly |
| 4 | **Licensed & insured** | Home trust bar, Home FAQ #5 (+ FAQ schema) | Remove the badge entirely if not confirmed |
| 5 | **Years in business** | Not used yet | Add to trust bar / hero once confirmed |
| 6 | **Services list** | Home services cards, Services page, form dropdown, `mailer.php` `$SERVICES`, schema | Built with your example list: Interior, Exterior, Cabinet Refinishing, Deck Staining, Drywall Repair. Commercial not included |
| 7 | **Scope bullets** ("What's included / Ideal for") | Services page, each section | Standard scope written — owner should confirm nothing is offered that they don't do |
| 8 | **Service towns** | Home service-area chips, footer, form dropdown, `mailer.php` `$TOWNS`, schema `areaServed` | Built with: Fitchburg, Madison, Verona, Middleton, Oregon, Monona, McFarland |

## Reviews
| 9 | **6 real Google reviews** — exact wording, first name + last initial, town | Home → Reviews section | 4 minimum; delete unused cards |

## Photos (replace files in `/images/`, keep names, 1200×800 WebP + 640px `-640.webp` copy)
| 10 | `hero.webp` | Home hero | Best finished exterior |
| 11 | `exterior-1-before/after` | Home + Our Work | |
| 12 | `interior-1-before/after` | Home + Our Work | |
| 13 | `cabinets-1-before/after` | Home + Our Work | |
| 14 | `exterior-2-before/after` | Our Work | |
| 15 | `interior-2-before/after` | Our Work | |
| 16 | `deck-1-before/after` | Our Work | Add/remove `<figure class="project">` blocks as needed |
| 17 | **Captions** `[Room/area] · [Town], WI — [what was done]` | Every project card | |
| 18 | **Alt text** `[TOWN]` | Every project image + hero | e.g. "Exterior house painting after — Verona, WI" |

## FAQ & offers
| 19 | Typical price range (optional) | Home FAQ #1 | Delete if you won't publish prices |
| 20 | Typical timelines | Home FAQ #4 | |
| 21 | Access policy | Home FAQ #6 | |
| 22 | Winter interior offer | Home FAQ #3, `script.js` banner (Nov–Apr) | Or remove the offer text |

## Technical
| 23 | `YOUR-DOMAIN.com` | All `<head>` canonicals/OG, JSON-LD, `robots.txt`, `sitemap.xml`, `mailer.php` `FROM_EMAIL` | |
| 24 | `FROM_EMAIL` on your domain | `mailer.php` | Needs SPF/DKIM or leads land in spam; SMTP (PHPMailer) recommended |
| 25 | HTTPS redirect | `.htaccess` | Uncomment once SSL is live |

## Launch notes
- Clean URLs (`/services`, `/our-work`) rely on `.htaccess` (Apache/LiteSpeed). On Nginx, add equivalent `try_files $uri $uri.html` rules.
- `aggregateRating` is included as requested, but Google doesn't show review stars for a business's own LocalBusiness markup. FAQ rich results are also limited to authoritative gov/health sites now — the markup still helps AI/semantic search.
- Update the 5.0 / 85 rating (visible text + JSON-LD on all three pages) whenever the review count grows.
- PHP upload limits: `upload_max_filesize ≥ 8M`, `post_max_size ≥ 26M`.
