# itflow-website

The itflow.org website. Plain PHP, no build step, no database: drop the files on any PHP 8 host.

Nothing is loaded from other sites. The Inter font and Font Awesome 6.5.2 are served from `assets/`, so pages work behind strict content policies and send visitors' addresses to no one.

## Editing content

Everything you'd change lives in **`config.php`**, top to bottom in page order:

| To change | Edit |
|---|---|
| Turn whole sections on or off | `$sections` |
| Site name, title, links to the demo, install guide, docs, forum, GitHub, Docker image | `$site` |
| Menu links | `$nav` |
| Top of the homepage: the "Free and open source" tag, heading, text, buttons, the hosting line, the dashboard screenshot, the four facts on the blue band | `$hero` |
| "Everything an MSP runs on" - the feature cards (icon, title, a sentence or two each) | `$features` |
| "ITFlow in action" - the six steps and their screenshots | `$tour` |
| "Get started" - host it yourself, hosted ITFlow, the demo | `$start` |
| Sponsors and their logos | `$sponsors` (logo files go in `assets/img/sponsors/`). Linked to ITFlow, paying sponsors are added after these by themselves |
| Sponsors section text | `$sponsors_section` |
| About timeline | `$timeline` (oldest first) |
| Core team | `$team` (photos go in `assets/img/team/`) |
| Footer text, social buttons, link columns | `$footer` |
| Hosting prices, regions and the fallback time zone | `$hosting` |
| Sponsorship tiers: name, price, icon, perks, the tier each one builds on, and `'listed'` for a place on itflow.org | `$sponsor_tiers` |
| Sign-up recipients and required fields | `$signup` |
| Mail server | `$mail` |

Prices are only ever in `$hosting` and `$sponsor_tiers` - the hosting line under the top buttons, the hosted card and the sign-up form all read them from there. In hosting text, `{price}`, `{included}` and `{seat}` become the base price, the technicians included and the price per extra technician.

Icons are [Font Awesome 6 free](https://fontawesome.com/search?o=r&m=free) class names, e.g. `fas fa-rocket` or `fab fa-github`. Add, remove or reorder feature cards freely - the grid fills four across on a wide screen.

### The logo

`logoHtml()` in `includes/bootstrap.php` draws the logo the way ITFlow's own sign-in page does: the Font Awesome paper plane and a bold "IT" in ITFlow blue (#0d6efd), then "Flow" in light white. It sits on the dark top bar and footer, so it reads exactly as in the app. It is text and an icon, so it stays sharp at any size and needs no image file. `assets/img/favicon.svg` is the same paper plane.

### Section switches

`$sections` turns each part of the site on or off. Switching a section off hides it everywhere it appears: the homepage section, its menu link, its buttons, and its part of the sign-up form.

| Switch | Controls |
|---|---|
| `features`, `tour`, `about`, `team` | The homepage sections of the same name |
| `start` | "Get started", its menu link (Hosting) and the "Get started" button at the top. With it off, the hosting line at the top goes straight to the sign-up |
| `hosting` | The hosted card in "Get started", the hosting line under the top buttons, and hosting on the sign-up form |
| `sponsors` | The sponsor logo wall and its menu link |
| `sponsorship` | Sponsorship tiers on the homepage and on the sign-up form |
| `signup` | The sign-up page itself. Off (or with both `hosting` and `sponsorship` off), `hosting.php` shows "Sign-ups are closed" and nothing links to it |
| `footer_sponsors` | The "Sponsored by ..." line in the footer |

### Required fields

`$signup['required']` lists which sign-up fields a customer must fill in: any of `address`, `city`, `state`, `postal`, `country`, `title`, `phone` (phone *or* mobile). Company name and the primary contact's name and email are always required. The form marks required fields with a red asterisk, checks them in the browser, and checks them again on the server.

### Time zone

The form doesn't ask for a time zone. The visitor's browser says which one they're in, and their ITFlow is set up in it. When the browser doesn't say, `$hosting['default_timezone']` is used instead. The sign-up email says which it was, and the customer can change it in ITFlow's settings afterwards.

### Links that preselect the sign-up

- `hosting.php?want=hosting` ticks hosted ITFlow
- `hosting.php?want=hosting&seats=7` also fills in 7 technicians (kept between the included number and `max_seats`)
- `hosting.php?want=sponsor` ticks sponsorship
- `hosting.php?want=sponsor&tier=website` ticks sponsorship and that tier (any key in `$sponsor_tiers`)

## Screenshots

Every screenshot is a set of WebP files in `assets/img/shots/`, named for what it shows:

- `NAME-light-1280.webp`, `NAME-light-2160.webp` - what most visitors see
- `NAME-dark-1280.webp`, `NAME-dark-2160.webp` - shown instead to visitors whose device is in dark mode (the whole site follows dark mode too)
- `NAME-1280.webp`, `NAME-2160.webp` - for something with one look only (the client portal)
- `NAME.webp`, or `NAME-light.webp` and `NAME-dark.webp` - one file each, for the phone screenshots

Phones download the 1280 file, sharp screens the 2160 one. Config entries name the set (`'shot' => 'tickets'`); a set whose files are missing is simply left out.

They were taken from ITFlow's own demo data (Maintenance > Starter Content), with the company renamed "Summit Ridge IT", at 1440 x 900 and 150% zoom.

To replace one, take new PNGs named `NAME-light.png` and `NAME-dark.png` into a folder and run:

```
python3 tools/webp.py that-folder
```

It writes both widths into `assets/img/shots/` and trims the scrollbar off the edge. It needs Python 3 and Pillow.

`assets/img/og.png` is the picture shown when a link to the site is shared (1200 x 630). It mentions the $60 price, so make it again if that changes: `tools/og.html` is its source - open it on the site in a 1200 x 630 browser window and save a screenshot over `og.png`.

## Sign-ups straight into ITFlow

Link the form to the billing ITFlow's Hosting add-on and sign-ups need nobody:

- **Hosted ITFlow** - the client, the recurring invoice and the first invoice are made there, and the customer goes straight to a Pay button. Their ITFlow is built as soon as that invoice is paid, and they get an email with a link to set their password.
- **Sponsorships** - the same: a recurring invoice for the tier and the first invoice with its Pay button. Once it is paid, the sponsor is emailed their white-label key, and staff get a to-do for the forum heart and the GitHub README.
- **Both together** - one client, an invoice each, and a Pay button each.

Put the two lines from Hosting > Settings in that ITFlow into `config.local.php`:

```php
$signup['itflow_url']    = 'https://billing.example.com';
$signup['itflow_secret'] = 'the secret shown there';
```

Linked, the prices, technicians and regions come from ITFlow (its Hosted ITFlow products and its servers) instead of `$hosting`, and the tier prices from its sponsorship products instead of `$sponsor_tiers` - kept for 10 minutes, so they are set in one place. The tiers' keys here (`forum`, `github`, `website`, `custom`) must be the add-on's. The itflow.app name is checked as it is typed. Each request to ITFlow is signed with the secret.

**Paying sponsors show by themselves.** Sponsors whose tier is `'listed'` (here, Website Company and Name Your Price, which includes it) are added to the logo wall and the footer line while they pay, after the sponsors in `$sponsors` - a name already there isn't shown twice. Their name, link and logo are set on their sponsor page in ITFlow; the page here picks them up within 10 minutes, and drops them the same way if an invoice goes overdue. Logos come inside ITFlow's answer as pictures, so nothing here loads from ITFlow.

If ITFlow can't be reached, or isn't taking sign-ups or sponsorships, the form emails the request like before - marked "NOT created" so someone adds it by hand - so no sign-up is lost.

## Mail

The sign-up form emails each request to `$signup['notify_to']` through PHPMailer 7.1.1 (`libs/PHPMailer/`) and stores nothing. SMTP is the default; set `'transport' => 'mail'` to use the server's own `mail()`. `$mail['timeout']` caps how long a slow mail server can hold the page.

Bots are kept out by a hidden field and a signed timestamp: a form sent within four seconds of loading, or more than a day later, or with a forged signature, looks sent but sends nothing.

## Secrets

Keep the real form secret and mail password out of git, in `config.local.php` next to `config.php` - `.gitignore` already excludes it. `config.local.example.php` shows what goes in it:

```php
<?php
$signup['form_secret']   = 'a-long-random-string';
$mail['password']        = 'smtp-password';
$signup['itflow_url']    = 'https://billing.example.com';   // optional - see "Sign-ups straight into ITFlow"
$signup['itflow_secret'] = 'from Hosting > Settings';
```

## Adding a page

```php
<?php
require_once __DIR__ . '/includes/bootstrap.php';
$page_title = 'Page title - ITFlow';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap page-head">
    <h1>Page title</h1>
</section>
<section class="wrap prose">
    <p>The page's text. Headings, paragraphs and lists inside .prose are styled for reading.</p>
</section>
<?php
require __DIR__ . '/includes/footer.php';
```

`terms.php` lives on the server only, so it isn't in this folder. To give it the new menu and footer, swap its top and bottom for the lines above and put its text inside `<section class="wrap prose">`.

## Before you upload

1. **Delete `index.html` on the server** if it is still there - many servers serve `index.html` ahead of `index.php`.
2. **Create `config.local.php`** with the real form secret and mail password.
3. **Update `terms.php`** as described above.
4. The old `assets/img/itflow.gif` and any old logo images are no longer used; the logo is drawn from text and the screenshots replace the animation.

## Files

| Path | What it is |
|---|---|
| `config.php` | All content and settings |
| `index.php` | The homepage - shows the parts in `includes/home/` that are switched on |
| `hosting.php` | The hosted ITFlow and sponsorship sign-up, and the Pay step after it |
| `includes/bootstrap.php` | Loads the config; small helpers (`e()`, `money()`, `shot()`, `logoHtml()`, `sectionOn()` ...) |
| `includes/header.php`, `includes/footer.php` | The top and bottom of every page |
| `includes/home/*.php` | Each homepage section |
| `includes/mailer.php` | Sending mail with PHPMailer |
| `includes/itflow_link.php` | The link to the billing ITFlow's Hosting add-on: signed requests, its prices, regions and tier prices, and the paying sponsors |
| `assets/css/site.css` | All the styles, light and dark |
| `assets/js/site.js` | The phone menu and "ITFlow in action" |
| `assets/fonts/` | Inter (SIL Open Font License, `OFL.txt`) |
| `assets/vendor/fontawesome/` | Font Awesome Free 6.5.2 (its licence in `LICENSE.txt`) |
| `libs/PHPMailer/` | PHPMailer 7.1.1 (LGPL 2.1) |
| `tools/webp.py` | Turns new screenshots into the site's files |
| `tools/og.html` | The source of the share picture, `assets/img/og.png` |
