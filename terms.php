<?php
/*
 * itflow.org - Terms of Use: the website, the demo, hosted ITFlow and
 * sponsorships. ITFlow itself is covered by its own license (AGPL-3.0).
 *
 * The details that are yours to decide are in $terms just below - check them
 * before this goes live, and change 'updated' whenever the terms change.
 */

require_once __DIR__ . '/includes/bootstrap.php';

$terms = [
    'operator'     => 'ITFlow',                                        // who "we" are - the business that bills hosting and sponsorships
    'email'        => 'sales@itflow.org',                              // where to write to us about any of this
    'law'          => 'the Commonwealth of Pennsylvania, United States', // whose laws these terms are under
    'updated'      => '2026-10-31',                                    // when these terms last changed, YYYY-MM-DD
    'suspend_days' => 14,   // an invoice this many days overdue suspends hosting, and takes sponsor perks off
    'remove_days'  => 30,   // a hosted ITFlow and its backups are removed this many days after it ends
    'notice_days'  => 30,   // notice we give before a price change, a change to these terms, or ending hosting
];

$updated = date('F j, Y', strtotime($terms['updated']));
$email = e($terms['email']);
$mailto = '<a href="mailto:' . $email . '">' . $email . '</a>';

$page_title = 'Terms of Use - ITFlow';
$page_description = 'The terms for itflow.org, the ITFlow demo, hosted ITFlow and sponsoring ITFlow.';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap page-head">
    <h1>Terms of Use</h1>
    <p>The terms for this website, the demo, hosted ITFlow and sponsoring ITFlow. Last updated <?= e($updated) ?>.</p>
</section>

<section class="wrap">
<div class="prose">
    <p><strong>The short version.</strong> ITFlow is free and open source, and its license is what covers the software. If you pay us for hosted ITFlow or sponsor the project, it's month to month and you can stop any time. Your data is yours. We don't sell anything about you, and this site doesn't track you.</p>

    <ul>
        <li><a href="#us">Who we are</a></li>
        <li><a href="#software">The ITFlow software</a></li>
        <li><a href="#site">This website and the demo</a></li>
        <li><a href="#hosting">Hosted ITFlow</a></li>
        <li><a href="#sponsorship">Sponsorship</a></li>
        <li><a href="#payment">Paying us</a></li>
        <li><a href="#privacy">Your information</a></li>
        <li><a href="#liability">Limits on our responsibility</a></li>
        <li><a href="#changes">Changes, the law, and getting in touch</a></li>
    </ul>

    <h2 id="us">Who we are</h2>
    <p>ITFlow is built by a small team who run their own MSPs. In these terms, "we" and "us" mean <?= e($terms['operator']) ?>, and "you" means you and the company you sign up for. By using this site, hosted ITFlow or a sponsorship, you agree to these terms.</p>

    <h2 id="software">The ITFlow software</h2>
    <p>ITFlow is free and open source under the <a href="https://www.gnu.org/licenses/agpl-3.0.en.html" target="_blank" rel="noopener">GNU Affero General Public License v3.0</a> (AGPL-3.0). That license, not these terms, covers using, copying, changing and sharing the software, and it says ITFlow comes as is, without warranty. Hosting with us or sponsoring the project doesn't change the license.</p>

    <h2 id="site">This website and the demo</h2>
    <p>We keep this site accurate, but it can change at any time, prices included. A price change for something you already pay for works as described under <a href="#payment">Paying us</a>. Links to other sites are for your convenience; we don't control them.</p>
    <p>The <a href="<?= e($site['demo_url']) ?>" target="_blank" rel="noopener">demo</a> is a shared ITFlow with a public sign-in, so anyone can see what's in it. Don't put real client information, passwords or anything private in it. We may reset it at any time.</p>

    <h2 id="hosting">Hosted ITFlow</h2>
    <h3>What you get</h3>
    <ul>
        <li>Your own ITFlow instance, not a shared one, in the region you choose, at your own domain or a name on <?= e($hosting['app_domain'] ?? 'itflow.app') ?>.</li>
        <li>As many technicians as you pay for. You can change the number at any time, and your price changes with it.</li>
        <li>Nightly backups.</li>
        <li>Updates applied for you: security fixes straight away, other releases shortly after they come out.</li>
        <li>Help with your hosted ITFlow from the ITFlow team.</li>
    </ul>
    <p>Your ITFlow sends email through the mail server you set up in it. Custom changes to ITFlow aren't part of hosting.</p>

    <h3>Your data</h3>
    <p>What you put in your ITFlow is yours. We only go into it to run, back up, update or fix your instance, when you ask us to, or when the law requires it. We don't look through it, use it for anything else or sell it. You can download a backup of your ITFlow from its own Backup page at any time. Because ITFlow is open source, you can take that backup and run ITFlow yourself whenever you like.</p>

    <h3>Looking after your account</h3>
    <p>You're responsible for who you give access to and for keeping sign-ins safe. We recommend two-factor sign-in for everyone. You're also responsible for what you and your users put in ITFlow, and for having the right to store your clients' information in it.</p>
    <p>Don't use hosted ITFlow to break the law, send spam, attack other systems, or put a heavy load on our servers on purpose. If you do, we may suspend your instance, and we'll tell you why.</p>

    <h3>Uptime</h3>
    <p>We work hard to keep your ITFlow running and fast, but we can't promise it will never be down. When we need planned downtime for maintenance, we keep it short and out of business hours where we can.</p>

    <h3>Unpaid invoices</h3>
    <p>If an invoice is more than <?= (int) $terms['suspend_days'] ?> days overdue, your ITFlow is suspended until it's paid. Nothing in it is deleted while it's suspended, and paying brings it straight back.</p>

    <h3>Cancelling</h3>
    <p>You can cancel at any time by emailing <?= $mailto ?>. Your ITFlow keeps running to the end of the month you've paid for. After it ends, we remove it and its backups after <?= (int) $terms['remove_days'] ?> days. Ask us before then and we'll send you a final backup.</p>
    <p>If we ever stop offering hosted ITFlow, we'll tell you at least <?= (int) $terms['notice_days'] ?> days ahead and help you move your data, to running ITFlow yourself or anywhere else.</p>

    <h2 id="sponsorship">Sponsorship</h2>
    <p>Sponsorship pays for ITFlow's development. You sponsor monthly at your tier's price, or at the amount you choose for Premier Sponsor, and you can stop at any time. Your perks are the ones listed for your tier when you sign up. Sponsorship isn't a support contract and doesn't buy a say in what gets built, though we always want to hear from you.</p>

    <h3>Your name and logo</h3>
    <p>While you sponsor, you let us show your name, your logo and a link to your website on itflow.org, as your tier describes. We may decline or take down a name, logo or link we don't think is right for the site; if we do, we'll tell you. If you sponsor anonymously, we don't show you anywhere. We still know who you are, because we need that to bill you.</p>

    <h3>The forum</h3>
    <p>Each forum username you give us gets a heart next to it on the ITFlow forum while you sponsor. The forum has its own rules.</p>

    <h3>White-label keys</h3>
    <p>Tiers that include white label come with a key that removes "Powered by ITFlow" from your own ITFlow. Each key lasts a year and is renewed every year while you sponsor. If you stop, the key you have keeps working until it runs out. Keep your key to yourself; don't publish or share it.</p>

    <h3>Online meetings</h3>
    <p>Premier Sponsors are invited to our online meetings, held about twice a year. We set the dates and what we cover.</p>

    <h3>Stopping or not paying</h3>
    <p>When you stop, your perks continue to the end of the month you've paid for. If an invoice is more than <?= (int) $terms['suspend_days'] ?> days overdue, your name or logo comes off itflow.org until it's paid.</p>

    <h2 id="payment">Paying us</h2>
    <ul>
        <li>Hosted ITFlow and sponsorships are billed monthly, in advance, in US dollars. Prices don't include any sales tax or VAT that applies.</li>
        <li>You can see and pay your invoices online. Card payments are handled by our payment processor; we never see or store your full card number.</li>
        <li>We don't refund part months, when you cancel or change your plan part way through a month.</li>
        <li>If we change the price of something you already pay for, we'll email you at least <?= (int) $terms['notice_days'] ?> days before your next invoice at the new price. You can cancel before then.</li>
    </ul>

    <h2 id="privacy">Your information</h2>
    <p>When you sign up, we get what you put in the form: your company, its address, your contacts, what you're signing up for and anything you write to us, along with the address your browser sent it from. It goes to our team by email and into our billing system.</p>
    <p>We use it to set up and bill your hosting or sponsorship and to contact you about them. We don't sell it or share it, except with the services we need to run things, such as our payment processor and our email provider. Tell us at <?= $mailto ?> if you'd like to see, correct or delete what we have; we keep billing records for as long as tax law requires.</p>
    <p>This website doesn't use cookies, analytics or tracking of any kind. Like any website, our server keeps standard logs of requests, including IP addresses, to keep the site running and secure.</p>

    <h2 id="liability">Limits on our responsibility</h2>
    <p>As far as the law allows, this site, the demo, hosted ITFlow and everything else we offer are provided as is, without any warranty. We aren't responsible for indirect or consequential losses, such as lost profits, lost business or lost data you could have restored from a backup. Our total responsibility for anything to do with these terms is limited to what you paid us in the 12 months before the claim.</p>

    <h2 id="changes">Changes, the law, and getting in touch</h2>
    <p>We may update these terms. The date at the top changes when we do. If a change matters to something you pay for, we'll email you at least <?= (int) $terms['notice_days'] ?> days before it applies to you.</p>
    <p>These terms are governed by the laws of <?= e($terms['law']) ?>.</p>
    <p>Questions about any of this? Email <?= $mailto ?>.</p>
</div>
</section>
<?php
require __DIR__ . '/includes/footer.php';
