// One look for every provider-area text field, select trigger and textarea:
// a soft cream fill with no visible border that turns white with a primary ring on focus.
export const fieldClass = 'block w-full rounded-xl border-2 border-transparent bg-ivt-paper-2 px-4 py-3 text-sm text-ivt-ink placeholder:text-ivt-ink-soft/50 transition-[background-color,border-color,box-shadow] duration-150 hover:bg-ivt-paper-3/70 focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10 disabled:cursor-not-allowed disabled:opacity-60';

// Added on top of `fieldClass` when the field has a validation error.
export const fieldErrorClass = '!border-danger-400 !bg-danger-50/60 focus:!ring-danger-400/15';
