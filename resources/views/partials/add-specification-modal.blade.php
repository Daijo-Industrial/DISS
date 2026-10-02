<x-modal name="add-specification-modal" maxWidth="md">
    <form method="POST" action="{{ route('admin.specifications.store') }}">
        @csrf
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Add Specification</h3>
            <button type="button" @click="$dispatch('close-modal', 'add-specification-modal')"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="p-6 space-y-4">
            <div>
                <label for="inputName" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    Specification Name
                </label>
                <input type="text" name="name" id="inputName" required
                    class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 outline-none transition"
                    placeholder="Enter specification name...">
            </div>
        </div>

        <div class="px-6 py-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2.5">
            <button type="button" @click="$dispatch('close-modal', 'add-specification-modal')"
                class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition shadow-2xs">
                Cancel
            </button>
            <button type="submit"
                class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-200 transition active:scale-95">
                Save Specification
            </button>
        </div>
    </form>
</x-modal>
