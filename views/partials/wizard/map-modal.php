<?php /** Modal opisu pinezki/trasy/obszaru - krok 10 */ ?>
<div id="pin-modal"
     class="fixed inset-0 hidden items-center justify-center bg-ink/60 dark:bg-night/80 backdrop-blur-sm"
     style="z-index: 9999"
     data-pin-modal>
    <div class="bg-paper dark:bg-deep rounded-3xl border border-mist/15 p-6 max-w-md w-[92%] shadow-pop">
        <h3 class="font-display font-bold text-xl text-ink dark:text-pale mb-1" data-modal-title>
            Dodaj opis
        </h3>
        <p class="text-sm text-mist mb-4">Co tu jest? Dlaczego zaznaczasz?</p>

        <label class="block text-sm font-medium text-ink dark:text-pale mb-1.5">Etykieta</label>
        <input type="text" data-modal-label maxlength="150" placeholder="np. Trogir, plaża"
               class="w-full mb-4 px-4 py-2.5 rounded-xl bg-cream dark:bg-night border-2 border-mist/20 focus:border-primary text-ink dark:text-pale outline-none transition">

        <label class="block text-sm font-medium text-ink dark:text-pale mb-1.5">Opis (opcjonalnie)</label>
        <textarea data-modal-description rows="3" maxlength="2000" placeholder="Dodatkowe info..."
                  class="w-full mb-4 px-4 py-2.5 rounded-xl bg-cream dark:bg-night border-2 border-mist/20 focus:border-primary text-ink dark:text-pale outline-none transition resize-none"></textarea>

        <div class="flex flex-wrap gap-2 justify-end">
            <button type="button" data-modal-cancel
                    class="px-5 py-2.5 rounded-full bg-mist/15 text-ink dark:text-pale font-medium hover:bg-mist/25 transition">
                Anuluj
            </button>
            <button type="button" data-modal-save
                    class="px-6 py-2.5 rounded-full bg-primary-deep text-white font-semibold hover:bg-primary transition">
                Zapisz
            </button>
        </div>
    </div>
</div>
