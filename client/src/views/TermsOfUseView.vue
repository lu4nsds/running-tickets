<template>
  <LegalPageLayout title="Termos de Uso" last-updated="21 de junho de 2026">
    <p>
      Estes Termos de Uso ("Termos") regulam o acesso e a utilização da plataforma
      Running Tickets ("Plataforma"), disponibilizada por meio deste site e seus
      aplicativos. Ao criar uma conta, navegar ou realizar uma inscrição, você declara
      que leu, compreendeu e concorda integralmente com estes Termos. Caso não concorde,
      não utilize a Plataforma.
    </p>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">1. Quem somos e o que fazemos</h2>
      <p>
        A Running Tickets é uma plataforma de intermediação que conecta organizadores de
        eventos esportivos (corridas de rua, maratonas, triatlos e similares) a
        participantes interessados em se inscrever nesses eventos. Atuamos como
        facilitadora da venda de inscrições e da emissão de ingressos, não sendo a
        realizadora dos eventos.
      </p>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">2. Cadastro e conta</h2>
      <ul class="list-disc space-y-2 pl-6">
        <li>É possível navegar e adquirir ingressos como visitante (checkout sem conta) ou com uma conta cadastrada.</li>
        <li>Ao se cadastrar, você se compromete a fornecer informações verdadeiras, completas e atualizadas.</li>
        <li>Você é responsável por manter a confidencialidade de suas credenciais de acesso e por toda atividade realizada em sua conta.</li>
        <li>A verificação de e-mail pode ser exigida para acesso completo às funcionalidades.</li>
      </ul>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">3. Inscrições, ingressos e pagamentos</h2>
      <ul class="list-disc space-y-2 pl-6">
        <li>O ingresso é o comprovante de inscrição e dá direito à participação no evento, conforme as regras definidas pelo organizador.</li>
        <li>Cada ingresso possui um código único (QR Code) e é nominal ao participante informado no momento da inscrição.</li>
        <li>Os pagamentos são processados pelo Mercado Pago. A Running Tickets não armazena dados completos de cartão de crédito.</li>
        <li>O ingresso somente é emitido após a confirmação do pagamento pelo provedor de pagamentos.</li>
        <li>Os valores, lotes, prazos de venda e cotas são definidos pelo organizador de cada evento.</li>
      </ul>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">4. Obrigações do usuário</h2>
      <ul class="list-disc space-y-2 pl-6">
        <li>Utilizar a Plataforma de forma lícita, respeitando estes Termos e a legislação vigente.</li>
        <li>Não tentar burlar, copiar, revender ou fraudar ingressos e códigos de validação.</li>
        <li>Não praticar atos que comprometam a segurança, a estabilidade ou a integridade da Plataforma.</li>
        <li>Fornecer dados corretos dos participantes, ciente de que informações incorretas podem inviabilizar a participação no evento.</li>
      </ul>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">5. Responsabilidades do organizador e da plataforma</h2>
      <p>
        A realização do evento, suas regras, percurso, estrutura, premiação, kits,
        eventuais cancelamentos ou adiamentos são de responsabilidade exclusiva do
        organizador. A Running Tickets é responsável pela disponibilização das
        ferramentas de venda, emissão e validação de ingressos, não respondendo por
        descumprimentos atribuíveis ao organizador.
      </p>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">6. Cancelamento e reembolso</h2>
      <p>
        As condições de cancelamento de inscrições e de reembolso de valores estão
        descritas em nossa
        <router-link to="/politica-de-reembolso" class="text-primary hover:underline">
          Política de Reembolso</router-link>, que integra estes Termos.
      </p>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">7. Propriedade intelectual</h2>
      <p>
        A marca, o logotipo, o layout, os textos, o código e demais elementos da
        Plataforma são protegidos por direitos de propriedade intelectual e não podem
        ser utilizados sem autorização prévia e expressa da Running Tickets.
      </p>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">8. Limitação de responsabilidade</h2>
      <p>
        A Plataforma é fornecida "no estado em que se encontra". Na máxima extensão
        permitida pela legislação aplicável, a Running Tickets não se responsabiliza por
        danos indiretos decorrentes do uso ou da impossibilidade de uso da Plataforma,
        nem por falhas atribuíveis a terceiros, como provedores de pagamento, conexão de
        internet ou os próprios organizadores.
      </p>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">9. Alterações destes Termos</h2>
      <p>
        Estes Termos podem ser atualizados a qualquer momento. A versão vigente estará
        sempre disponível nesta página, com a respectiva data de atualização. O uso
        continuado da Plataforma após alterações representa concordância com a nova
        versão.
      </p>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">10. Legislação e foro</h2>
      <p>
        Estes Termos são regidos pelas leis da República Federativa do Brasil. Fica
        eleito o foro do domicílio do consumidor para dirimir eventuais controvérsias,
        salvo disposição legal em contrário.
      </p>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">11. Contato</h2>
      <p>
        Em caso de dúvidas sobre estes Termos, entre em contato pelos canais de suporte
        disponibilizados na Plataforma.
      </p>
    </section>
  </LegalPageLayout>
</template>

<script setup>
import LegalPageLayout from "../components/LegalPageLayout.vue";
</script>
