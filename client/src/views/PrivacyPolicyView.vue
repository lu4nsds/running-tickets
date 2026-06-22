<template>
  <LegalPageLayout title="Política de Privacidade" last-updated="21 de junho de 2026">
    <p>
      Esta Política de Privacidade descreve como a Running Tickets ("nós") coleta, usa,
      compartilha e protege os dados pessoais dos usuários e participantes ("você"), em
      conformidade com a Lei nº 13.709/2018 (Lei Geral de Proteção de Dados — LGPD).
    </p>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">1. Dados que coletamos</h2>
      <ul class="list-disc space-y-2 pl-6">
        <li><span class="text-white">Dados de cadastro:</span> nome, e-mail e senha (armazenada de forma criptografada). No login social, recebemos seu identificador e e-mail do Google.</li>
        <li><span class="text-white">Dados de participante:</span> nome, e-mail, CPF, idade e demais informações necessárias para a inscrição no evento.</li>
        <li><span class="text-white">Dados do comprador:</span> e-mail utilizado na compra, que pode ser diferente do participante.</li>
        <li><span class="text-white">Dados de pagamento:</span> processados diretamente pelo Mercado Pago. Não armazenamos os dados completos do seu cartão.</li>
        <li><span class="text-white">Dados de navegação:</span> informações técnicas como endereço IP e dados de uso, para segurança e melhoria do serviço.</li>
      </ul>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">2. Finalidades do tratamento</h2>
      <ul class="list-disc space-y-2 pl-6">
        <li>Processar inscrições, pagamentos e a emissão de ingressos.</li>
        <li>Permitir a validação de ingressos no check-in dos eventos.</li>
        <li>Enviar comunicações transacionais (confirmação de pedido, ingressos, recuperação de senha).</li>
        <li>Garantir a segurança, prevenir fraudes e cumprir obrigações legais e regulatórias.</li>
      </ul>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">3. Base legal</h2>
      <p>
        O tratamento dos seus dados se fundamenta principalmente na execução do contrato
        (realização da inscrição e prestação do serviço), no cumprimento de obrigações
        legais, no legítimo interesse para segurança e prevenção a fraudes e, quando
        aplicável, no seu consentimento.
      </p>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">4. Compartilhamento de dados</h2>
      <p>Seus dados podem ser compartilhados com:</p>
      <ul class="list-disc space-y-2 pl-6">
        <li><span class="text-white">Organizadores do evento:</span> recebem os dados dos participantes necessários para a realização e gestão do evento.</li>
        <li><span class="text-white">Mercado Pago:</span> para processamento e validação dos pagamentos.</li>
        <li><span class="text-white">Provedores de infraestrutura:</span> como a AWS (armazenamento) e a Resend (envio de e-mails).</li>
        <li><span class="text-white">Google:</span> quando você opta pelo login social.</li>
        <li><span class="text-white">Autoridades:</span> quando exigido por lei ou ordem judicial.</li>
      </ul>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">5. Cookies</h2>
      <p>
        Utilizamos cookies e tecnologias semelhantes para manter sua sessão autenticada,
        lembrar preferências e analisar o uso da Plataforma. Você pode gerenciar os
        cookies nas configurações do seu navegador, ciente de que alguns recursos podem
        deixar de funcionar corretamente.
      </p>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">6. Seus direitos como titular</h2>
      <p>Nos termos da LGPD, você pode, a qualquer momento, solicitar:</p>
      <ul class="list-disc space-y-2 pl-6">
        <li>Confirmação da existência de tratamento e acesso aos seus dados.</li>
        <li>Correção de dados incompletos, inexatos ou desatualizados.</li>
        <li>Anonimização, bloqueio ou eliminação de dados desnecessários.</li>
        <li>Portabilidade e informação sobre o compartilhamento de dados.</li>
        <li>Revogação do consentimento, quando aplicável.</li>
      </ul>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">7. Segurança</h2>
      <p>
        Adotamos medidas técnicas e organizacionais para proteger seus dados contra
        acessos não autorizados, perda ou alteração, incluindo criptografia de senhas e
        controle de acesso. Nenhum sistema, contudo, é completamente imune a riscos.
      </p>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">8. Retenção de dados</h2>
      <p>
        Mantemos seus dados pelo tempo necessário ao cumprimento das finalidades aqui
        descritas e das obrigações legais (como prazos fiscais e de auditoria de
        pagamentos), após o que serão eliminados ou anonimizados.
      </p>
    </section>

    <section class="space-y-3">
      <h2 class="text-2xl font-bold text-white">9. Contato e encarregado (DPO)</h2>
      <p>
        Para exercer seus direitos ou esclarecer dúvidas sobre o tratamento dos seus
        dados, entre em contato com nosso encarregado de proteção de dados pelos canais
        de suporte disponibilizados na Plataforma.
      </p>
    </section>
  </LegalPageLayout>
</template>

<script setup>
import LegalPageLayout from "../components/LegalPageLayout.vue";
</script>
