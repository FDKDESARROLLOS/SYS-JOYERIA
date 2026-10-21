<div
    id="delete-confirmation"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#222222]/25 px-5 backdrop-blur-sm dark:bg-[#111412]/55"
    role="presentation"
>
    <div
        class="w-full max-w-md rounded-2xl border border-aurea-border bg-white p-6 shadow-[0_18px_50px_rgba(35,42,37,0.16)] dark:bg-[#2d302e]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="delete-confirmation-title"
        aria-describedby="delete-confirmation-description"
    >
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#f9e9e6] text-aurea-danger">
                <x-admin.icon name="trash" class="h-5 w-5" />
            </div>
            <div>
                <h2 id="delete-confirmation-title" class="font-serif text-xl text-aurea-ink">¿Eliminar este registro?</h2>
                <p id="delete-confirmation-description" class="mt-2 text-sm leading-6 text-aurea-muted">Esta acción eliminará el registro. ¿Deseas continuar?</p>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <button
                type="button"
                id="delete-confirmation-cancel"
                class="aurea-focus rounded-lg border border-aurea-border px-4 py-2.5 text-sm font-medium text-aurea-muted hover:bg-aurea-paper"
            >Cancelar</button>
            <button
                type="button"
                id="delete-confirmation-confirm"
                class="aurea-focus rounded-lg bg-aurea-danger px-4 py-2.5 text-sm font-medium text-white hover:bg-[#8f554a]"
            >Eliminar</button>
        </div>
    </div>
</div>
