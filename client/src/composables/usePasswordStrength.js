import { computed } from 'vue';

export function usePasswordStrength(password) {
  const requirements = computed(() => {
    const pwd = password.value || '';
    
    return {
      length: {
        met: pwd.length >= 8,
        label: 'Mínimo de 8 caracteres',
      },
      lowercase: {
        met: /[a-z]/.test(pwd),
        label: 'Pelo menos uma letra minúscula',
      },
      uppercase: {
        met: /[A-Z]/.test(pwd),
        label: 'Pelo menos uma letra maiúscula',
      },
      number: {
        met: /[0-9]/.test(pwd),
        label: 'Pelo menos um número',
      },
    };
  });

  const score = computed(() => {
    const reqs = requirements.value;
    let points = 0;
    
    if (reqs.length.met) points++;
    if (reqs.lowercase.met) points++;
    if (reqs.uppercase.met) points++;
    if (reqs.number.met) points++;
    
    return points;
  });

  const strength = computed(() => {
    const s = score.value;
    
    if (s === 0 || password.value === '') {
      return {
        score: 0,
        label: '',
        color: 'bg-gray-200',
        textColor: 'text-gray-500',
      };
    }
    
    if (s <= 2) {
      return {
        score: s,
        label: 'Fraca',
        color: 'bg-red-500',
        textColor: 'text-red-600',
      };
    }
    
    if (s === 3) {
      return {
        score: s,
        label: 'Média',
        color: 'bg-yellow-500',
        textColor: 'text-yellow-600',
      };
    }
    
    return {
      score: s,
      label: 'Forte',
      color: 'bg-green-500',
      textColor: 'text-green-600',
    };
  });

  const isValid = computed(() => {
    return score.value === 4;
  });

  const width = computed(() => {
    return `${(score.value / 4) * 100}%`;
  });

  return {
    requirements,
    score,
    strength,
    isValid,
    width,
  };
}
