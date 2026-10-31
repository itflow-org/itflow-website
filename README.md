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
| Sponsors, longest-standing first: `'tier'` is how each shows - `'premier'` (Premier Sponsor) a large tile with their logo, `'company'` (Sponsor) a smaller logo tile (logo files go in `assets/img/sponsors/`), no tier (a Supporter) their name; `'since' => 'YYYY-MM'` when each started | `$sponsors`. Linked to ITFlow, paying sponsors are added by themselves |
| Sponsors section text, the three headings, the anonymous sponsors' line (`'anonymous'`) and the "Your logo here" tile (`'join'`) | `$sponsors_section` |
| About timeline | `$timeline` (oldest first) |
| Core team | `$team` (photos go in `assets/img/team/`) |
| Footer text, social buttons, link columns | `$footer` |
| Hosting prices, regions and the fallback time zone | `$hosting` |
| Sponsorship tiers: name, price, icon, perks, the tier each one builds on, how its sponsors show on itflow.org (`'show'` - `'name'`, `'logo'` or `'large'`) and `'whitelabel'` for a white-label key | `$sponsor_tiers` |
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
| `signup` | The sign-up page itself. Off (or with both `hosting` and `sponsorship` off), `signup.php` shows "Sign-ups are closed" and nothing links to it |

### Required fields

`$signup['required']` lists which sign-up fields a customer must fill in: any of `address`, `city`, `state`, `postal`, `country`, `title`, `phone` (phone *or* mobile). Company name and the primary contact's name and email are always required. The form marks required fields with a red asterisk, checks them in the browser, and checks them again on the server.

### Time zone

The form doesn't ask for a time zone. The visitor's browser says which one they're in, and their ITFlow is set up in it. When the browser doesn't say, `$hosting['default_timezone']` is used instead. The sign-up email says which it was, and the customer can change it in ITFlow's settings afterwards.

### Links that preselect the sign-up

- `signup.php?want=hosting` ticks hosted ITFlow
- `signup.php?want=hosting&seats=7` also fills in 7 technicians (kept between the included number and `max_seats`)
- `signup.php?want=sponsor` ticks sponsorship
- `signup.php?want=sponsor&tier=premier` ticks sponsorship and that tier (any key in `$sponsor_tiers`)

To share, `sponsor.php` is the short link to sponsoring (`sponsor.php?tier=premier` picks a tier too): it goes to the sign-up with sponsorship ticked while it's open, and otherwise to where the "Your logo here" tile points. `hosting.php`, the sign-up's old address, still works - it sends visitors on to `signup.php` with their `?want=` and the rest.

On the form, any sponsor can choose to sponsor anonymously - no name, logo or website on itflow.org, everything else in their tier the same - and give as many forum usernames as they like for the forum hearts.

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
- **Sponsorships** - the same: a recurring invoice for the tier and the first invoice with its Pay button. Once it is paid, the sponsor is emailed their white-label key (on a tier with one), and staff get a to-do for the forum hearts. Anonymous sponsors and every forum username go along with it.
- **Both together** - one client, an invoice each, and a Pay button each.

Put the two lines from Hosting > Settings in that ITFlow into `config.local.php`:

```php
$signup['itflow_url']    = 'https://billing.example.com';
$signup['itflow_secret'] = 'the secret shown there';
```

Linked, the prices, technicians and regions come from ITFlow (its Hosted ITFlow products and its servers) instead of `$hosting`, and the tier prices from its sponsorship products instead of `$sponsor_tiers` - kept for 10 minutes, so they are set in one place. The tiers' keys here (`supporter`, `company`, `premier`) must be the add-on's. The itflow.app name is checked as it is typed. Each request to ITFlow is signed with the secret.

**Paying sponsors show by themselves.** Sponsors who aren't anonymous are added while they pay, by their tier - Premier Sponsors' large logos, Sponsors' logos, Supporters' names - after the sponsors in `$sponsors`; a name already there isn't shown twice. (When ITFlow doesn't say which tier, a featured sponsor shows as a Premier Sponsor and the rest in the Sponsor tier.) Their name, link and logo are set on their sponsor page in ITFlow; the page here picks them up within 10 minutes, and drops them the same way if an invoice goes overdue. Logos come inside ITFlow's answer as pictures, so nothing here loads from ITFlow.

If ITFlow can't be reached, or isn't taking sign-ups or sponsorships, the form emails the request like before - marked "NOT created" so someone adds it by hand - so no sign-up is lost.

## Mail

The sign-up form emails each request to `$signup['notify_to']` through PHPMailer 7.1.1 (`libs/PHPMailer/`) and stores nothing. SMTP is the default; set `'transport' => 'mail'` to use the server's own `mail()`. `$mail['timeout']` caps how long a slow mail server can hold the page.

Bots are kept out by a hidden field and a signed timestamp: a form sent within four seconds of loading, or more than a day later, or with a forged signature, looks sent but sends nothing.

## Secrets

Keep the real form secret and mail password out of git, in `config.local.php` next to `config.php` - `.gitignore` already excludes it. Copy `config.local.example.php` to start it: the secrets are at the top, and below them, commented out, every setting a server usually changes - the mail server, who gets sign-ups and the fields they need, the section switches, the site's own address (for a staging copy) and the hosting prices used while the form isn't linked to ITFlow. config.php reads it last, so anything set there wins, on that server only.

```php
<?php
$signup['form_secret']   = 'a-long-random-string';          // required - openssl rand -hex 32
$mail['password']        = 'smtp-password';
$signup['itflow_url']    = 'https://billing.example.com';   // optional - see "Sign-ups straight into ITFlow"
$signup['itflow_secret'] = 'from Hosting > Settings';
```

The form secret has to be set: config.php's own is public, so with it the bot check keeps nobody out.

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
2. **Create `config.local.php`** from `config.local.example.php`, with the real form secret and mail password.
3. **Update `terms.php`** as described above.
4. The old `assets/img/itflow.gif` and any old logo images are no longer used; the logo is drawn from text and the screenshots replace the animation.

## Files

| Path | What it is |
|---|---|
| `config.php` | All content and settings |
| `index.php` | The homepage - shows the parts in `includes/home/` that are switched on |
| `signup.php` | The hosted ITFlow and sponsorship sign-up, and the Pay step after it |
| `sponsor.php` | The short link to sponsoring - on to the sign-up, or to the "Your logo here" tile's address while sign-ups are closed |
| `hosting.php` | The sign-up's old address - sends visitors on to `signup.php` |
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
