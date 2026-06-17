<?php
require_once __DIR__ . '/../config/settings.php';
$social = getSettings();
?>
<div class="flex space-x-5">
  <?php if (!empty($social['social_facebook']) && $social['social_facebook'] !== '#'): ?>
   <a href="<?= htmlspecialchars($social['social_facebook'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="text-blue-200 hover:text-white transition-colors transform hover:scale-110" aria-label="Facebook">
    <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd"/></svg>
  </a>
  <?php endif; ?>
  <?php if (!empty($social['social_twitter']) && $social['social_twitter'] !== '#'): ?>
  <a href="<?= htmlspecialchars($social['social_twitter']) ?>" target="_blank" rel="noopener" class="text-blue-200 hover:text-white transition-colors transform hover:scale-110" aria-label="Twitter / X">
    <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
  </a>
  <?php endif; ?>
  <?php if (!empty($social['social_instagram']) && $social['social_instagram'] !== '#'): ?>
  <a href="<?= htmlspecialchars($social['social_instagram']) ?>" target="_blank" rel="noopener" class="text-blue-200 hover:text-white transition-colors transform hover:scale-110" aria-label="Instagram">
    <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772 4.902 4.902 0 011.772-1.153c.636-.247 1.363-.416 2.427-.465 1.024-.047 1.379-.06 3.808-.06h.63z"/></svg>
  </a>
  <?php endif; ?>
  <?php if (!empty($social['social_youtube']) && $social['social_youtube'] !== '#'): ?>
  <a href="<?= htmlspecialchars($social['social_youtube']) ?>" target="_blank" rel="noopener" class="text-blue-200 hover:text-white transition-colors transform hover:scale-110" aria-label="YouTube">
    <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M19.812 5.418c.861.23 1.538.907 1.768 1.768C22 8.746 22 12 22 12s0 3.255-.418 4.814a2.504 2.504 0 01-1.768 1.768c-1.56.419-7.814.419-7.814.419s-6.255 0-7.814-.419a2.505 2.505 0 01-1.768-1.768C2 15.255 2 12 2 12s0-3.255.417-4.814a2.507 2.507 0 011.768-1.768C5.744 5 11.998 5 11.998 5s6.255 0 7.814.418zM9.5 8.975v6.05l5.25-3.025L9.5 8.975z"/></svg>
  </a>
  <?php endif; ?>
  <?php if (!empty($social['social_tiktok']) && $social['social_tiktok'] !== '#'): ?>
  <a href="<?= htmlspecialchars($social['social_tiktok']) ?>" target="_blank" rel="noopener" class="text-blue-200 hover:text-white transition-colors transform hover:scale-110" aria-label="TikTok">
    <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
  </a>
  <?php endif; ?>
  <?php if (empty(array_filter(array_intersect_key($social, array_flip(['social_facebook','social_twitter','social_instagram','social_youtube','social_tiktok'])), fn($v) => $v !== '' && $v !== '#'))): ?>
    <p class="text-blue-200 text-sm">Próximamente redes sociales</p>
  <?php endif; ?>
</div>
