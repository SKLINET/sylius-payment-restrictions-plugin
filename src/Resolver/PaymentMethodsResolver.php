<?php

declare(strict_types=1);

namespace ThreeBRS\SyliusPaymentRestrictionPlugin\Resolver;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Sylius\Component\Payment\Model\PaymentInterface as BasePaymentInterface;
use Sylius\Component\Payment\Resolver\PaymentMethodsResolverInterface;
use ThreeBRS\SyliusPaymentRestrictionPlugin\Model\ThreeBRSSyliusResolvePaymentMethodForOrder;
use Webmozart\Assert\Assert;

class PaymentMethodsResolver implements PaymentMethodsResolverInterface
{
    /** @var PaymentMethodRepositoryInterface */
    private $paymentMethodRepository;

    /** @var ThreeBRSSyliusResolvePaymentMethodForOrder */
    private $paymentOrderResolver;
	/**
	 * @var PaymentMethodsResolverInterface
	 */
	private $decorated;

	public function __construct(
	    PaymentMethodsResolverInterface $decorated,
        PaymentMethodRepositoryInterface $paymentMethodRepository,
        ThreeBRSSyliusResolvePaymentMethodForOrder $paymentOrderResolver
    ) {
		$this->decorated = $decorated;
		$this->paymentMethodRepository = $paymentMethodRepository;
        $this->paymentOrderResolver = $paymentOrderResolver;
	}

    /**
     * @inheritdoc
     */
    public function getSupportedMethods(BasePaymentInterface $payment): array
    {
	    $enabledForChannel = $this->decorated->getSupportedMethods($payment);
		//
	    Assert::isInstanceOf($payment, PaymentInterface::class);
        Assert::true($this->supports($payment), 'This payment method is not support by resolver');

        $order = $payment->getOrder();
		Assert::isInstanceOf($order, OrderInterface::class);

        $channel = $order->getChannel();
	    Assert::isInstanceOf($channel, ChannelInterface::class);

        $result = [];
        foreach ($enabledForChannel as $paymentMethod) {
            if ($this->paymentOrderResolver->isEligible($paymentMethod, $order)) {
                $result[] = $paymentMethod;
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function supports(BasePaymentInterface $payment): bool
    {
        if (
            !$payment instanceof PaymentInterface ||
            $payment->getOrder() === null
        ) {
            return false;
        }

        $order = $payment->getOrder();

        return
            $order instanceof OrderInterface &&
            $order->getChannel() !== null;
    }
}