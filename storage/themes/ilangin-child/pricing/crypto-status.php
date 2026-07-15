<?php
$status = (string) $transaction->status;
$provider = isset($transaction->metadata->provider) && is_object($transaction->metadata->provider)
    ? $transaction->metadata->provider
    : new stdClass;
$payAmount = isset($provider->pay_amount) ? (string) $provider->pay_amount : (string) $transaction->pay_amount;
?>
<section class="bg-section-secondary py-5">
    <div class="container" style="max-width: 880px">
        <?php echo message() ?>
        <div class="card border-0 shadow-sm overflow-hidden" data-nowpayments-status-card data-status-url="<?php echo route('checkout.crypto.status.json', [$transaction->order_id]) ?>">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-wrap align-items-start justify-content-between mb-4">
                    <div class="pr-3">
                        <p class="text-uppercase text-primary font-weight-bold text-sm mb-2"><?php ee('Crypto payment') ?></p>
                        <h1 class="h3 mb-2"><?php ee('Complete your transfer') ?></h1>
                        <p class="text-muted mb-0"><?php ee('Access starts only after final blockchain settlement.') ?></p>
                    </div>
                    <span class="badge badge-primary px-3 py-2 text-uppercase" data-nowpayments-state aria-live="polite"><?php echo e($status) ?></span>
                </div>
                <div class="alert alert-info" role="status">
                    <?php ee('Send the exact amount in the selected network. A different asset or network can permanently lose funds.') ?>
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted"><?php ee('Amount') ?></dt>
                    <dd class="col-sm-8"><strong class="h5"><?php echo e($payAmount).' '.e(strtoupper((string) $transaction->pay_currency)) ?></strong></dd>
                    <dt class="col-sm-4 text-muted"><?php ee('Payment address') ?></dt>
                    <dd class="col-sm-8"><code class="d-block text-break p-3 bg-light rounded" data-copy-value><?php echo e((string) $transaction->pay_address) ?></code></dd>
                    <?php if($transaction->payin_extra_id): ?>
                        <dt class="col-sm-4 text-muted"><?php ee('Memo or tag') ?></dt>
                        <dd class="col-sm-8"><code><?php echo e((string) $transaction->payin_extra_id) ?></code></dd>
                    <?php endif ?>
                    <dt class="col-sm-4 text-muted"><?php ee('Order') ?></dt>
                    <dd class="col-sm-8"><code><?php echo e((string) $transaction->order_id) ?></code></dd>
                    <dt class="col-sm-4 text-muted"><?php ee('Expires') ?></dt>
                    <dd class="col-sm-8"><?php echo e((string) ($transaction->expires_at ?: e('Provider deadline pending'))) ?></dd>
                </dl>
                <div class="border-top mt-4 pt-4">
                    <p class="mb-2" data-nowpayments-message aria-live="polite"><?php ee('Waiting for payment. This page updates automatically.') ?></p>
                    <a class="btn btn-outline-primary btn-sm" href="<?php echo route('dashboard') ?>"><?php ee('Return to dashboard') ?></a>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
(function () {
    const card = document.querySelector('[data-nowpayments-status-card]');
    if (!card) return;
    const state = card.querySelector('[data-nowpayments-state]');
    const message = card.querySelector('[data-nowpayments-message]');
    const terminal = new Set(['paid', 'expired', 'failed', 'refunded', 'canceled']);
    let timer;
    const poll = async () => {
        try {
            const response = await fetch(card.dataset.statusUrl, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
            if (!response.ok) throw new Error('status');
            const data = await response.json();
            state.textContent = data.status;
            message.textContent = data.status === 'paid'
                ? '<?php echo addslashes(e('Payment settled. Your access is active.')) ?>'
                : '<?php echo addslashes(e('Payment status updated.')) ?> ' + data.status;
            if (!terminal.has(data.status)) timer = window.setTimeout(poll, 10000);
        } catch (error) {
            message.textContent = '<?php echo addslashes(e('Status refresh failed. You can safely reload this page.')) ?>';
            timer = window.setTimeout(poll, 20000);
        }
    };
    if (!terminal.has(state.textContent.trim().toLowerCase())) timer = window.setTimeout(poll, 5000);
    window.addEventListener('beforeunload', () => window.clearTimeout(timer));
})();
</script>
<style>
@media (prefers-reduced-motion: reduce) {
    [data-nowpayments-status-card] * { scroll-behavior: auto !important; transition: none !important; }
}
</style>

