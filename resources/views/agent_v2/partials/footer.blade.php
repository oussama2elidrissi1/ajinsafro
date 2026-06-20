@once
    <style>
        .aj-agent-shell-footer {
            width: 100%;
            padding: 18px 28px 24px;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 400;
        }

        .aj-agent-shell-footer__inner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            max-width: 1500px;
            margin: 0 auto;
            padding-top: 14px;
            border-top: 1px solid #e6edf5;
        }

        .aj-agent-shell-footer__inner span {
            font-weight: 400;
        }

        .aj-agent-shell-footer__inner span + span::before {
            content: "";
            display: inline-block;
            width: 4px;
            height: 4px;
            margin-right: 12px;
            border-radius: 999px;
            background: #cbd5e1;
            vertical-align: middle;
        }

        @media (max-width: 640px) {
            .aj-agent-shell-footer {
                padding-inline: 18px;
            }

            .aj-agent-shell-footer__inner {
                flex-direction: column;
                gap: 4px;
            }

            .aj-agent-shell-footer__inner span + span::before {
                display: none;
            }
        }
    </style>
@endonce

<footer class="aj-agent-shell-footer">
    <div class="aj-agent-shell-footer__inner">
        <span>{{ date('Y') }} © Ajinsafro.ma</span>
        <span>Espace agent</span>
    </div>
</footer>
