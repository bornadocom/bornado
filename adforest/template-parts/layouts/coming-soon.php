<?php
get_header();
global $adforest_theme; ?>
<section class="comming-soon-grid">
    <div class="container">
        <div class="row">
            <div class="col-xs-12 comming-soon">
                <div class="theme-logo">
                    <a href="<?php echo esc_url(home_url('/')); ?>">
                        <?php
                        if (isset($adforest_theme['sb_comming_soon_logo']['url']) && $adforest_theme['sb_comming_soon_logo']['url'] != "") {
                            ?>
                            <img src="<?php echo esc_url($adforest_theme['sb_comming_soon_logo']['url']); ?>"
                                 alt="<?php echo esc_attr__('Site Logo', 'adforest'); ?>">
                            <?php
                        } else {
                            ?>
                            <img src="<?php echo esc_url(trailingslashit(get_template_directory_uri())) . 'images/logo.png' ?>"
                                 alt="<?php echo esc_attr__('Site Logo', 'adforest'); ?>"/>
                            <?php
                        }
                        ?>
                    </a>
                    <input type="hidden" id="when_live"
                           value="<?php echo esc_attr($adforest_theme['sb_comming_soon_date']); ?>"/>
                    <input type="hidden" id="get_time"
                           value="<span>%w</span><?php echo __('weeks', 'adforest'); ?><span>%d</span> <?php echo __('days', 'adforest'); ?> <span>%H</span> <?php echo __('hr', 'adforest'); ?><span>%M</span> <?php echo __('min', 'adforest'); ?> <span>%S</span><?php echo __('sec', 'adforest'); ?></span>"/>

                </div>
                <div class="count-down">
                    <div id="clock"></div>
                </div>
                <div class="subscribe">
                    <p><?php echo wp_kses($adforest_theme['sb_comming_soon_title'], adforest_required_tags()); ?>

                    </p>
                    <?php

                    if (isset($adforest_theme['coming_soon_notify']) && $adforest_theme['coming_soon_notify']) {
                        ?>
                        <form method="post">
                            <input type="text" name="sb_email" id="sb_email"
                                   placeholder="<?php echo esc_attr__('Valid E-mail Address', 'adforest'); ?>"
                                   autocomplete="off">
                            <button class="adt-button-dark" type="button" id="save_email">
                                <i class="fa fa-paper-plane-o" aria-hidden="true"></i>
                                <?php echo esc_html__('Notify Me', 'adforest'); ?>
                            </button>
                            <button class="adt-button-dark" type="button" id="processing_req">
                                <i class="fa fa-paper-plane-o" aria-hidden="true"></i>
                                <?php echo esc_html__('Processing...', 'adforest'); ?>
                            </button>
                            <input type="hidden" id="sb_action" value="coming_soon"/>
                        </form>
                        <?php
                    }
                    ?>
                </div>
                <div class="social-area-share">
                    <?php
                    foreach ($adforest_theme['social_media_soon'] as $index => $val) {
                        ?>
                        <?php
                        if ($val != "") {
                            ?>
                            <a href="<?php echo esc_url($val); ?>" target="_blank">
                                <i class="<?php echo adforest_social_icons($index); ?>" aria-hidden="true"></i>
                            </a>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const whenLive = document.getElementById('when_live')?.value;
        const getTimeFormat = document.getElementById('get_time')?.value;

        if (whenLive && document.getElementById('clock')) {
            const targetDate = new Date(whenLive).getTime();
            const clockEl = document.getElementById('clock');

            const updateClock = () => {
                const now = new Date().getTime();
                let t = targetDate - now;

                if (t < 0) {
                    clockEl.innerHTML = "<?php echo esc_js(__('We are live!', 'adforest')); ?>";
                    clearInterval(interval);
                    return;
                }

                const seconds = Math.floor((t / 1000) % 60);
                const minutes = Math.floor((t / 1000 / 60) % 60);
                const hours = Math.floor((t / (1000 * 60 * 60)) % 24);
                const days = Math.floor((t / (1000 * 60 * 60 * 24)) % 7);
                const weeks = Math.floor(t / (1000 * 60 * 60 * 24 * 7));

                let formatted = getTimeFormat
                    .replace('%w', weeks)
                    .replace('%d', days)
                    .replace('%H', hours)
                    .replace('%M', minutes)
                    .replace('%S', seconds);

                clockEl.innerHTML = formatted;
            };

            updateClock();
            const interval = setInterval(updateClock, 1000);
        }

        // Email Notify AJAX
        const saveEmailBtn = document.getElementById('save_email');
        const processingBtn = document.getElementById('processing_req');
        const emailField = document.getElementById('sb_email');

        if (saveEmailBtn && processingBtn && emailField) {
            processingBtn.style.display = 'none';

            saveEmailBtn.addEventListener('click', function () {
                const email = emailField.value.trim();
                if (!email || !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                    alert("<?php echo esc_js(__('Please enter a valid email address.', 'adforest')); ?>");
                    return;
                }

                saveEmailBtn.style.display = 'none';
                processingBtn.style.display = 'inline-block';

                const data = new FormData();
                data.append('action', 'coming_soon');
                data.append('sb_email', email);
                const nonce = document.getElementById('coming_soon_nonce') ? document.getElementById('coming_soon_nonce').value : '';
                data.append('security', nonce);

                fetch("<?php echo esc_url(admin_url('admin-ajax.php')); ?>", {
                    method: 'POST',
                    body: data,
                })
                    .then(response => response.text())
                    .then(res => {
                        alert(res);
                        saveEmailBtn.style.display = 'inline-block';
                        processingBtn.style.display = 'none';
                        emailField.value = '';
                    })
                    .catch(error => {
                        alert("<?php echo esc_js(__('Something went wrong. Please try again.', 'adforest')); ?>");
                        saveEmailBtn.style.display = 'inline-block';
                        processingBtn.style.display = 'none';
                    });
            });
        }
    });
</script>
<?php wp_footer(); ?>
</body>
</html>