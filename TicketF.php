<?php
class Ticket {
    private $venta;
    private $detalles;
    private $config;

    public function __construct($venta, $detalles, $config = []) {
        $this->venta = $venta;
        $this->detalles = $detalles;
        $this->config = array_merge([
            'empresa' => 'Jardín Literario',
            'direccion' => '----------',
            'telefono' => '----------',
            'rfc' => '----------',
            'mensaje_footer' => '¡Gracias por su compra!',
            'logo_url' => './Imagenes/Logo.png', 
            'color_principal' => '#2c3e50',
            'color_secundario' => '#3498db',
            'logo_width' => '120px' 
        ], $config);
    }

    public function generar() {
        $fecha = new DateTime($this->venta['Fecha']);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Ticket <?= $this->venta['Folio'] ?></title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
            <style>
                body {
                    font-family: 'Arial', sans-serif;
                    font-size: 12px;
                    width: 80mm;
                    margin: 0 auto;
                    padding: 5px;
                    color: #333;
                }
                .ticket-container {
                    border: 2px solid <?= $this->config['color_principal'] ?>;
                    border-radius: 8px;
                    padding: 10px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 10px;
                    padding-bottom: 8px;
                    border-bottom: 2px dashed <?= $this->config['color_principal'] ?>;
                }
                .logo {
                    max-width: <?= $this->config['logo_width'] ?>;
                    height: auto;
                    margin-bottom: 10px;
                    display: block;
                    margin-left: auto;
                    margin-right: auto;
                }
                .title { 
                    font-size: 16px; 
                    font-weight: bold;
                    color: <?= $this->config['color_principal'] ?>;
                    margin-bottom: 5px;
                }
                .subtitle {
                    font-size: 12px;
                    color: <?= $this->config['color_secundario'] ?>;
                }
                .info { 
                    margin: 4px 0;
                    line-height: 1.3;
                }
                .info strong {
                    color: <?= $this->config['color_principal'] ?>;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin: 10px 0;
                    font-size: 11px;
                }
                th { 
                    text-align: left; 
                    border-bottom: 2px solid <?= $this->config['color_principal'] ?>; 
                    padding: 4px 0;
                    color: <?= $this->config['color_principal'] ?>;
                }
                td { 
                    padding: 4px 0;
                    border-bottom: 1px dashed #ddd;
                }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .total { 
                    font-weight: bold; 
                    text-align: right; 
                    margin-top: 10px; 
                    font-size: 14px;
                    color: <?= $this->config['color_principal'] ?>;
                }
                .footer { 
                    text-align: center; 
                    margin-top: 15px;
                    padding-top: 8px;
                    border-top: 2px dashed <?= $this->config['color_principal'] ?>;
                    font-size: 10px;
                    color: #777;
                }
                .divider {
                    border-top: 1px dashed <?= $this->config['color_principal'] ?>;
                    margin: 8px 0;
                }
                .badge {
                    display: inline-block;
                    padding: 2px 6px;
                    border-radius: 4px;
                    background: <?= $this->config['color_principal'] ?>;
                    color: white;
                    font-size: 10px;
                }
                .btn-print {
                    background: <?= $this->config['color_principal'] ?>;
                    color: white;
                    border: none;
                    padding: 6px 12px;
                    border-radius: 4px;
                    cursor: pointer;
                    margin: 5px;
                }
                .btn-close {
                    background: #e74c3c;
                    color: white;
                    border: none;
                    padding: 6px 12px;
                    border-radius: 4px;
                    cursor: pointer;
                    margin: 5px;
                }
                @media print {
                    body { 
                        width: 80mm !important; 
                        margin: 0 !important; 
                        padding: 2mm !important; 
                        border: none;
                    }
                    .no-print { display: none !important; }
                    .ticket-container {
                        border: none;
                        box-shadow: none;
                    }
                    .logo {
                        max-width: <?= $this->config['logo_width'] ?>;
                        filter: grayscale(100%) contrast(120%);
                    }
                }
            </style>
        </head>
        <body>
            <div class="ticket-container">
                <div class="header">
                    <?php if(!empty($this->config['logo_url'])): ?>
                        <img src="<?= $this->config['logo_url'] ?>" class="logo" alt="Logo Jardín Literario">
                    <?php endif; ?>
                    <div class="title"><?= $this->config['empresa'] ?></div>
                    <div class="subtitle"><?= $this->config['direccion'] ?></div>
                    <div class="info">Tel: <?= $this->config['telefono'] ?> | RFC: <?= $this->config['rfc'] ?></div>
                </div>
                <div class="info"><strong><i class="bi bi-receipt"></i> TICKET:</strong> <span class="badge"><?= $this->venta['Folio'] ?></span></div>
                <div class="info"><strong><i class="bi bi-calendar"></i> FECHA:</strong> <?= $fecha->format('d/m/Y H:i') ?></div>
                <div class="info"><strong><i class="bi bi-person"></i> CLIENTE:</strong> <?= $this->venta['Cliente'] ?></div>
                <div class="info"><strong><i class="bi bi-person-badge"></i> ATENDIÓ:</strong> <?= $this->venta['Vendedor'] ?></div>
                <div class="info"><strong><i class="bi bi-credit-card"></i> PAGO:</strong> <?= $this->venta['FormaPago'] ?></div>
                <div class="divider"></div>
                <table>
                    <thead>
                        <tr>
                            <th>DESCRIPCIÓN</th>
                            <th class="text-right">CANT</th>
                            <th class="text-right">IMPORTE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->detalles as $item): ?>
                        <tr>
                            <td><?= $item['Titulo'] ?></td>
                            <td class="text-right"><?= $item['Cantidad'] ?></td>
                            <td class="text-right">$<?= number_format($item['Subtotal'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="divider"></div>
                <div class="total">
                    TOTAL: $<?= number_format($this->venta['Total'], 2) ?>
                </div>
                <div class="footer">
                    <div><?= $this->config['mensaje_footer'] ?></div>
                    <div><i class="bi bi-clock"></i> <?= date('d/m/Y H:i') ?></div>
                </div>
                <div class="no-print text-center" style="margin-top: 15px;">
                    <button class="btn-print" onclick="window.print()">
                        <i class="bi bi-printer"></i> Imprimir
                    </button>
                    <button class="btn-close" onclick="window.close()">
                        <i class="bi bi-x-circle"></i> Cerrar
                    </button>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}