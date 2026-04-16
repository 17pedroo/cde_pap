<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";
require_login();

page_header("Sobre o Projeto");
?>

<div class="row g-3">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="mb-1">Sobre o Projeto</h4>
        <div class="text-muted">PAP – TGPSI (12.º ano)</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title">Objetivo</h5>
        <p class="mb-0">
          Desenvolver um sistema de <b>cartão digital</b> acessível num site responsivo
          para PC e smartphone, permitindo ao aluno apresentar um <b>QR Code</b>,
          consultar <b>saldo</b> e <b>movimentos</b>, e à portaria registar
          <b>entradas e saídas</b>.
        </p>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title">Funcionalidades</h5>
        <ul class="mb-0">
          <li>Login de aluno, encarregado e admin.</li>
          <li>Painel do aluno com saldo, QR dinâmico e histórico de movimentos.</li>
          <li>Painel do encarregado com carregamentos, compras do bar, acessos e cantina do aluno.</li>
          <li>Painel central de admin com acesso aos módulos principais.</li>
          <li>Leitura de QR para registo de entradas e saídas.</li>
          <li>Registo automático de acessos com histórico e filtros.</li>
          <li>Gestão de compras no bar e buffet por QR Code.</li>
          <li>Gestão de senhas de cantina.</li>
          <li>Registo e gestão de alunos e encarregados.</li>
          <li>Gestão de produtos e relatórios com exportação.</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Tecnologias Utilizadas</h5>
        <ul class="mb-0">
          <li>PHP</li>
          <li>MySQL / phpMyAdmin</li>
          <li>HTML, CSS e Bootstrap</li>
          <li>JavaScript</li>
          <li>Visual Studio Code</li>
          <li>IA</li>
          <li>qrcodejs e html5-qrcode</li>
          <li>Railway</li>
          <li>WampServer</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title">Base de Dados</h5>
        <ul class="mb-0">
          <li><code>users</code> – utilizadores</li>
          <li><code>wallets</code> – saldo</li>
          <li><code>wallet_transactions</code> – movimentos</li>
          <li><code>access_logs</code> – entradas e saídas</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title">Segurança</h5>
        <ul class="mb-0">
          <li>Autenticação por sessão com controlo de acesso por perfil.</li>
          <li>Palavras-passe protegidas com hash.</li>
          <li>QR dinâmico, temporário e de uso único.</li>
          <li>Validação dos QR Codes no servidor antes de cada registo.</li>
          <li>Dados fictícios para cumprir RGPD.</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Melhorias Futuras</h5>
        <ul class="mb-0">
          <li>Integração com leitores QR Code físicos.</li>
          <li>Implementação de um novo sistema para funcionários.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php page_footer(); ?>