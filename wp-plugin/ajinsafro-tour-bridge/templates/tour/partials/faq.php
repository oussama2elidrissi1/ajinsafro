<?php
/**
 * FAQ Partial - Frequently Asked Questions Accordion
 *
 * @var array $tour Tour data
 * @package AjinsafroTourBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$faqs = $tour['faqs'] ?? [];

if (empty($faqs)) {
    return;
}
?>

<section class="ajtb-section" id="faq">
    <h2 class="ajtb-section-title">
        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
        Questions Fréquentes
    </h2>

    <div class="ajtb-faq-accordion">
        <?php foreach ($faqs as $index => $faq): 
            $question = $faq['question'] ?? '';
            $answer = $faq['answer'] ?? '';
            
            if (empty($question)) {
                continue;
            }
        ?>
            <div class="faq-item" data-faq="<?php echo $index; ?>">
                <button class="faq-question" 
                        aria-expanded="false" 
                        aria-controls="faq-answer-<?php echo $index; ?>">
                    <span class="question-text"><?php echo esc_html($question); ?></span>
                    <span class="question-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer" id="faq-answer-<?php echo $index; ?>">
                    <div class="answer-content">
                        <?php echo wp_kses_post($answer); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Contact CTA -->
    <div class="ajtb-faq-cta">
        <p>Vous avez d'autres questions ?</p>
        <a href="<?php echo esc_url(home_url('/contact')); ?>" class="btn-outline">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
            </svg>
            Contactez-nous
        </a>
    </div>
</section>
