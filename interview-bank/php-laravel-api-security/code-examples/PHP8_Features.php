<?php

declare(strict_types=1);

namespace App\Examples;

/**
 * Comprehensive PHP 8.x Features Demonstration
 */

// ============================================================================
// PHP 8.0 FEATURES
// ============================================================================

// 1. NAMED ARGUMENTS
function createPayment(
    string $orderId,
    float $amount,
    string $currency = 'USD',
    ?string $description = null,
    bool $captureImmediately = true
): array {
    return compact('orderId', 'amount', 'currency', 'description', 'captureImmediately');
}

// Usage with named arguments
$payment = createPayment(
    orderId: 'ORD-12345',
    amount: 99.99,
    currency: 'EUR',
    captureImmediately: false
);

// 2. ATTRIBUTES (Annotations)
#[\Attribute(\Attribute::TARGET_METHOD)]
class RateLimit
{
    public function __construct(
        public int $maxAttempts = 60,
        public int $decayMinutes = 1
    ) {}
}

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class RequiresPermission
{
    public function __construct(
        public string|array $permissions
    ) {}
}

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Encrypted
{
    public function __construct(
        public string $algorithm = 'AES-256-CBC'
    ) {}
}

class PaymentApiController
{
    #[RateLimit(maxAttempts: 10, decayMinutes: 1)]
    #[RequiresPermission('payment.create')]
    public function createPayment(): void
    {
        // Implementation
    }
}

// Reading attributes
$reflectionMethod = new \ReflectionMethod(PaymentApiController::class, 'createPayment');
$attributes = $reflectionMethod->getAttributes(RateLimit::class);

foreach ($attributes as $attribute) {
    $rateLimit = $attribute->newInstance();
    echo "Max Attempts: {$rateLimit->maxAttempts}\n";
}

// 3. UNION TYPES
function processPayment(int|float $amount): string
{
    return number_format($amount, 2);
}

function findPayment(int|string $identifier): ?Payment
{
    if (is_int($identifier)) {
        return Payment::find($identifier);
    }
    return Payment::where('transaction_id', $identifier)->first();
}

class PaymentProcessor
{
    private int|float $amount;
    private string|null $description = null;
}

// 4. NULLSAFE OPERATOR
class User
{
    public ?Profile $profile = null;
}

class Profile
{
    public ?Address $address = null;
}

class Address
{
    public ?string $country = null;
}

$user = new User();

// Old way
$country = $user->profile !== null 
    ? ($user->profile->address !== null 
        ? $user->profile->address->country 
        : null)
    : null;

// PHP 8.0+ Nullsafe operator
$country = $user?->profile?->address?->country;

// 5. MATCH EXPRESSION (Improved switch)
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
}

function getStatusMessage(PaymentStatus $status): string
{
    return match($status) {
        PaymentStatus::PENDING => 'Payment is being processed',
        PaymentStatus::COMPLETED => 'Payment successful',
        PaymentStatus::FAILED => 'Payment failed',
        PaymentStatus::REFUNDED => 'Payment refunded',
    };
}

// Match with conditions
function calculateFee(float $amount): float
{
    return match(true) {
        $amount < 10 => 0.50,
        $amount < 100 => $amount * 0.05,
        $amount < 1000 => $amount * 0.03,
        default => $amount * 0.02,
    };
}

// 6. CONSTRUCTOR PROPERTY PROMOTION
class Payment
{
    // Old way
    // private string $id;
    // private float $amount;
    // private string $currency;
    
    // public function __construct(string $id, float $amount, string $currency)
    // {
    //     $this->id = $id;
    //     $this->amount = $amount;
    //     $this->currency = $currency;
    // }
    
    // PHP 8.0+ Constructor Property Promotion
    public function __construct(
        private string $id,
        private float $amount,
        private string $currency,
        private PaymentStatus $status = PaymentStatus::PENDING
    ) {}
    
    public function getAmount(): float
    {
        return $this->amount;
    }
}

// 7. MIXED TYPE
function processData(mixed $data): mixed
{
    return match(true) {
        is_array($data) => json_encode($data),
        is_string($data) => json_decode($data, true),
        is_object($data) => (array) $data,
        default => $data,
    };
}

// 8. THROW EXPRESSION
class PaymentValidator
{
    public function validate(array $data): array
    {
        return [
            'amount' => $data['amount'] ?? throw new \InvalidArgumentException('Amount required'),
            'currency' => $data['currency'] ?? throw new \InvalidArgumentException('Currency required'),
        ];
    }
}

// ============================================================================
// PHP 8.1 FEATURES
// ============================================================================

// 1. ENUMS (Backed and Pure)

// Pure enum
enum Role
{
    case ADMIN;
    case USER;
    case GUEST;
}

// Backed enum with methods
enum PaymentMethod: string
{
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case PAYPAL = 'paypal';
    case STRIPE = 'stripe';
    
    public function label(): string
    {
        return match($this) {
            self::CREDIT_CARD => 'Credit Card',
            self::DEBIT_CARD => 'Debit Card',
            self::PAYPAL => 'PayPal',
            self::STRIPE => 'Stripe',
        };
    }
    
    public function processingFee(): float
    {
        return match($this) {
            self::CREDIT_CARD => 2.9,
            self::DEBIT_CARD => 1.5,
            self::PAYPAL => 3.5,
            self::STRIPE => 2.9,
        };
    }
}

// Using enums
$method = PaymentMethod::CREDIT_CARD;
echo $method->label(); // "Credit Card"
echo $method->value; // "credit_card"
echo $method->processingFee(); // 2.9

// Enum with interface
interface Colorable
{
    public function color(): string;
}

enum Priority: int implements Colorable
{
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
    
    public function color(): string
    {
        return match($this) {
            self::LOW => 'green',
            self::MEDIUM => 'yellow',
            self::HIGH => 'red',
        };
    }
}

// 2. READONLY PROPERTIES
class PaymentDto
{
    public function __construct(
        public readonly string $id,
        public readonly float $amount,
        public readonly string $currency,
    ) {}
    
    // This would cause error:
    // $dto->amount = 100; // Error: Cannot modify readonly property
}

// 3. FIRST-CLASS CALLABLE SYNTAX
class Calculator
{
    public function add(int $a, int $b): int
    {
        return $a + $b;
    }
}

$calculator = new Calculator();

// Old way
$callback = [$calculator, 'add'];
$result = $callback(1, 2);

// PHP 8.1+ First-class callable
$callback = $calculator->add(...);
$result = $callback(1, 2);

// 4. NEW IN INITIALIZERS
class Service
{
    private Logger $logger;
    
    public function __construct(
        private LoggerInterface $customLogger = new NullLogger()
    ) {
        $this->logger = $customLogger;
    }
}

// 5. INTERSECTION TYPES
interface Renderable
{
    public function render(): string;
}

interface Loggable
{
    public function log(): void;
}

class PaymentReport implements Renderable, Loggable
{
    public function render(): string
    {
        return 'Payment Report';
    }
    
    public function log(): void
    {
        // Log implementation
    }
}

function processReport(Renderable&Loggable $report): void
{
    $report->log();
    echo $report->render();
}

// 6. NEVER RETURN TYPE
function terminate(string $message): never
{
    throw new \RuntimeException($message);
}

function redirect(string $url): never
{
    header("Location: {$url}");
    exit;
}

// 7. FINAL CLASS CONSTANTS
class Config
{
    final public const API_VERSION = 'v1';
}

// Cannot override in child class
// class ExtendedConfig extends Config
// {
//     public const API_VERSION = 'v2'; // Error
// }

// ============================================================================
// PHP 8.2 FEATURES
// ============================================================================

// 1. READONLY CLASSES
readonly class PaymentRequest
{
    public function __construct(
        public string $orderId,
        public float $amount,
        public string $currency,
        public ?string $description = null
    ) {}
}

// All properties are automatically readonly

// 2. DISJUNCTIVE NORMAL FORM (DNF) TYPES
interface Cacheable {}
interface Serializable {}

class CacheableEntity implements Cacheable {}
class SerializableEntity implements Serializable {}

// DNF: (A&B)|(C&D)
function processEntity((Cacheable&Serializable)|null $entity): void
{
    if ($entity !== null) {
        // Process entity
    }
}

// 3. NULL, FALSE, AND TRUE AS STANDALONE TYPES
function alwaysNull(): null
{
    return null;
}

function checkCondition(): true
{
    return true;
}

function failedCheck(): false
{
    return false;
}

// 4. CONSTANTS IN TRAITS
trait PaymentTrait
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
}

class Order
{
    use PaymentTrait;
    
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}

// 5. DEPRECATE DYNAMIC PROPERTIES
class StrictClass
{
    public string $defined;
    
    // PHP 8.2+ warns on dynamic properties
    // $obj->undefinedProperty = 'value'; // Deprecated warning
}

// To allow dynamic properties, use attribute:
#[\AllowDynamicProperties]
class FlexibleClass
{
    // Dynamic properties allowed
}

// ============================================================================
// PRACTICAL EXAMPLES COMBINING FEATURES
// ============================================================================

// Payment Processing with Modern PHP
readonly class CreatePaymentCommand
{
    public function __construct(
        public string $orderId,
        public float $amount,
        public string $currency,
        public PaymentMethod $method,
    ) {}
}

enum TransactionStatus: string
{
    case INITIATED = 'initiated';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    
    public function isTerminal(): bool
    {
        return match($this) {
            self::COMPLETED, self::FAILED, self::CANCELLED => true,
            default => false,
        };
    }
    
    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::INITIATED => in_array($newStatus, [self::PROCESSING, self::CANCELLED]),
            self::PROCESSING => in_array($newStatus, [self::COMPLETED, self::FAILED]),
            default => false,
        };
    }
}

class ModernPaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly Logger $logger,
    ) {}
    
    #[RateLimit(maxAttempts: 5)]
    public function process(CreatePaymentCommand $command): Payment|PaymentError
    {
        try {
            $result = $this->gateway->charge(
                amount: $command->amount,
                currency: $command->currency,
                method: $command->method,
            );
            
            return match($result->status) {
                TransactionStatus::COMPLETED => new Payment(
                    id: $result->id,
                    amount: $command->amount,
                    status: TransactionStatus::COMPLETED,
                ),
                TransactionStatus::FAILED => new PaymentError(
                    message: $result->errorMessage ?? 'Payment failed',
                    code: $result->errorCode ?? 'UNKNOWN',
                ),
                default => throw new \UnexpectedValueException('Unexpected status'),
            };
        } catch (\Throwable $e) {
            $this->logger->error('Payment processing failed', [
                'order_id' => $command->orderId,
                'error' => $e->getMessage(),
            ]);
            
            return new PaymentError(
                message: 'Payment processing failed',
                code: 'PROCESSING_ERROR',
            );
        }
    }
}

// Type-safe configuration with enums
enum Environment: string
{
    case LOCAL = 'local';
    case STAGING = 'staging';
    case PRODUCTION = 'production';
    
    public function isProduction(): bool
    {
        return $this === self::PRODUCTION;
    }
    
    public function debug(): bool
    {
        return !$this->isProduction();
    }
}

readonly class ApplicationConfig
{
    public function __construct(
        public Environment $environment,
        public string $apiKey,
        public string $apiSecret,
        public int $timeout = 30,
    ) {}
    
    public static function fromEnv(): self
    {
        return new self(
            environment: Environment::from($_ENV['APP_ENV'] ?? 'local'),
            apiKey: $_ENV['API_KEY'] ?? throw new \RuntimeException('API_KEY not set'),
            apiSecret: $_ENV['API_SECRET'] ?? throw new \RuntimeException('API_SECRET not set'),
            timeout: (int) ($_ENV['API_TIMEOUT'] ?? 30),
        );
    }
}

// Advanced enum usage
enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case INR = 'INR';
    
    public function symbol(): string
    {
        return match($this) {
            self::USD => '$',
            self::EUR => '€',
            self::GBP => '£',
            self::INR => '₹',
        };
    }
    
    public function format(float $amount): string
    {
        return match($this) {
            self::USD, self::GBP => $this->symbol() . number_format($amount, 2),
            self::EUR => number_format($amount, 2) . ' ' . $this->symbol(),
            self::INR => $this->symbol() . number_format($amount, 2),
        };
    }
    
    public static function getPopular(): array
    {
        return [self::USD, self::EUR, self::GBP];
    }
}

// Usage
$price = Currency::USD->format(1234.56); // "$1,234.56"
$price = Currency::EUR->format(1234.56); // "1,234.56 €"
