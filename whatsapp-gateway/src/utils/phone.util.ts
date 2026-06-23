// Normalise a phone number to the digits-only, country-prefixed form Baileys
// expects. Brazilian local numbers (10 or 11 digits) get the `55` country code;
// anything else is returned as bare digits, assumed to already be in E.164.
export function normalizeBrazilPhone(value: string): string {
  const digits = value.replace(/\D/g, '');

  if (digits.length === 10 || digits.length === 11) {
    return `55${digits}`;
  }

  return digits;
}
