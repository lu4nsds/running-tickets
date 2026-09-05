import { normalizeBrazilPhone } from '@src/utils/phone.util';

describe('normalizeBrazilPhone', () => {
  it('prefixes 55 to an 11-digit local mobile number', () => {
    expect(normalizeBrazilPhone('11999998888')).toBe('5511999998888');
  });

  it('prefixes 55 to a 10-digit local landline number', () => {
    expect(normalizeBrazilPhone('1133334444')).toBe('551133334444');
  });

  it('strips non-digit characters before normalising', () => {
    expect(normalizeBrazilPhone('(11) 99999-8888')).toBe('5511999998888');
  });

  it('leaves an already country-prefixed number untouched', () => {
    expect(normalizeBrazilPhone('5511999998888')).toBe('5511999998888');
  });

  it('returns bare digits for lengths outside the local range', () => {
    // 12 digits — neither 10 nor 11 — so no country code is added.
    expect(normalizeBrazilPhone('+351 912 345 678')).toBe('351912345678');
  });

  it('returns an empty string when there are no digits', () => {
    expect(normalizeBrazilPhone('abc')).toBe('');
  });
});
