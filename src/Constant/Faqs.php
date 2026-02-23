<?php

namespace App\Constant;

use App\Service\StoreInfoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Faqs extends AbstractController
{
    public function __construct(private readonly StoreInfoService $storeInfoService)
    {
    }

    const EMAIL_LINK = '<a href="mailto: sales@yardsignplus.com">sales@yardsignplus.com</a>';
    const TEL_LINK = '<a href="tel: +1-877-958-1499">+1-877-958-1499</a>';
    const LIVE_CHAT = '<a href="#" onclick="Tawk_API.toggle(); return false;"  oncontextmenu="return false;">live chat </a>';

    const MY_ACCOUNT = '<a href="/login">My Account</a>';

    const FAQS_TITLE = 'We offer a variety of yard signs that can be fully customized. You can place orders for many different designs. We have the resources to take care of your yard sign related requirements. For any explanation regarding our services, modes of payment, refunds, discounts, and more, you may go through our Frequently Asked Questions (FAQs).';

    public function getFaqs(): array
    {
        return [
            'Frequently Asked Questions' => [
                'Where is your company?' => 'We are located in Houston, TX. All custom signs are produced and shipped from Houston, TX.',
                'Can I get a quotation?' => 'To receive a quote, please simply follow the Steps on our editor page. Alternatively, you can ping us on our <a href="#" onclick="Tawk_API.toggle(); return false;"  oncontextmenu="return false;">live chat </a>, call us at <a href="tel: +1-877-958-1499">+1-877-958-1499</a>, or email us at <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a>.',
                'What are your boards made of?' => 'Our signs are made up of corrugated plastic or coroplast.',
                'How are my designs placed on the sign?' => 'We apply UV (ultraviolet) light to cure and dry your customizations onto the
                                                            corrugated plastic via UV printing. We then cut and prepare your order to ship. All
                                                            signs are UV printed. We produce fully custom, outdoor yard signs. We can create any
                                                            design! All signs are waterproof and can be placed outside. Custom prints do not
                                                            fade or smear. We offer multiple sizes to choose from. All orders deliver in as
                                                            little as 1 day. Once you submit your order we will send you a proof or design for
                                                            your review. We do not begin production until you approve your proof. You can also
                                                            review how your sign will look by editing your design on our live editor.',
                'What is your minimum order quantity?' => 'We have no minimum order quantities across all of our products. You may purchase as many items as you would like.',
                'What is your turnaround time?' => 'We have the fastest turnaround time in the nation. Standard industry turnaround is 7
                                        to 14 business days for production and 3 to 7 additional business days for shipping.
                                        However, with us, our turnaround time including production and shipping is 1 to 5
                                        business days. You may choose the preferred delivery date before submitting your
                                        order. The turnaround time including production and shipping is considered after
                                        receiving payment for the order and the approval of the proof or preview image we
                                        send you. All proofs are created and emailed within an average time of 1 hour.',
                'How do I make a change to the order that I placed?' => 'Contact our sales team by calling <a href="tel: +1-877-958-1499">+1-877-958-1499</a> or emailing <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a>. and we will try our best to apply your changes as long as your order is not already in production.',
                'I want custom signs. Is your price the lowest?' => 'Our prices are ALWAYS the lowest in the market for custom signs. We can assure you that there will be no company in the market that will be able to provide you a lower price and a faster turnaround time than us.',
                'Can I get a sample before I order?' => 'Please visit our '. self::generateLink('order_sample', 'Order Samples') .' page to submit an order for samples. We will deliver sample orders within 1 to 3 business days for you to
                                        review our quality and sizing. You can also order one or a few of your exact designs
                                        directly from our editor pages as we have no minimum order quantities on most
                                        products. Feel free to call us at <a href="tel:+18779581499"><a href="tel: +1-877-958-1499">+1-877-958-1499</a></a> if you have any questions.',
                'How do I submit a repeat order?' => 'Please visit our '. self::generateLink('repeat_order', 'repeat orders page') .' to submit a repeat order. You may also choose to visit the same product type that you would like to reorder. 
                                        You do not need to find the same design or style. You can disregard the style and colors on our editor page. Please simply add your new sizes, quantity, characters, and artwork (if any). 
                                        Choose the same upgrades as your initial order (if any). Then, mention your old order number in the comments section. We will send you a digital proof in 1 hour for your review after you have submitted your order. 
                                        The proof will be identical in style and colors to your referenced order while utilizing your latest inputs. If you have any questions on how to submit a repeat order, give us a call at ' . self::TEL_LINK . ' or message us on our <a href="#" onclick="Tawk_API.toggle(); return false;"  oncontextmenu="return false;">live chat </a>.',
                'What is the orientation for printing?' => 'We print in landscape, horizontal orientation by default.',
                'How many sides can I print on?' => 'You can print on single or double sided (both sides). We offer full color printing. We can print and produce any design.',
                'Can you produce any design?' => 'Yes we create fully custom designs. For assistance, simply leave a comment and submit your order. Upload your custom design or artwork files if available. We can help create and align your order for free. We can create any design. We will email you a free digital proof in 1 hour. Once approved, we will begin production. You can Request Changes as many times as needed via the proof link.',
                'Can I pay after receiving a proof?' => 'Yes you can! On the checkout page, please select See Design - Pay Later if you prefer to pay after you are satisfied with your proof. We will only begin production once you approve your proof.',
                'Do you assist with custom designs or artwork files?' => 'Yes we offer free expert design customization assistance. This includes free custom artwork creation and free proofs. Simply leave us a comment. Once you submit your order, we will send a free proof for your review. You can Request Changes before you Approve for Production.',
                'Are there any setup or custom design fees?' => 'There are no setup or custom design fees. We do not have any hidden fees.',
                'How do I place an order for different sized signs?' => 'To order, please visit our ' . self::generateLink('custom_yard_sign_editor', 'editor page', ['variant'=>'24x18']) .  '. On Step 1 you may Choose Your Sizes (inches). For custom sizes, please choose Order Custom Sizes on Step 1 and enter your measurements. Once you submit your order we will send a free proof for your review in 1 hour. You may Request Changes if needed before you Approve for Production. For any questions please call <a href="tel: +1-877-958-1499">+1-877-958-1499</a>, email <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a>, or message us on our <a href="#" onclick="Tawk_API.toggle(); return false;"  oncontextmenu="return false;">Live Chat</a>. We are available 24 hours a day, 7 days a week.',
                'How soon can orders be delivered?' => 'We have the fastest turnaround time in the nation. Standard industry turnaround is 7 to 14 business days for production and 3 to 7 additional business days for shipping. However, with us, our turnaround time including production and shipping is 1 to 5 business days. You may choose the preferred delivery date before submitting your order. The turnaround time including production and shipping is considered after receiving payment for the order and the approval of the proof or preview image we send you. All proofs are created and emailed within an average time of 1 hour.',
                'Do you offer pickup?' => 'Yes we do! Pickup is offered for all delivery dates from our local warehouse in Houston, Texas. Please choose your delivery date, then contact us to change your order to pickup. We will discount your delivery fee by 50% (does not apply to same day delivery).',
                'Do you offer big head cutouts (faces) and die-cut (custom shape) signs?' => 'Yes we do! To order please '. self::generateLink('category', 'shop our big head cutout SKUs', ['slug' => 'big-head-cutouts']) .'  or our '. self::generateLink('category', 'die-cut SKUs', ['slug' => 'die-cut']) .'. We can create any design including using your own photos. We offer bulk discounts. To order, please visit our ' . self::generateLink('custom_yard_sign_editor', 'editor page', ['variant'=>'24x18']) .  '. On Step 1 you may Choose Your Sizes (inches) and enter the quantity. On Step 2 you may Choose Your Sides (Single or Double). On Step 3 upload your artwork or choose from our templates. Once you submit your order we will send a free proof for your review in 1 hour. You may Request Changes if needed before you Approve for Production. For any questions please call <a href="tel: +1-877-958-1499">+1-877-958-1499</a>, email <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a>, or message us on our <a href="#" onclick="Tawk_API.toggle(); return false;"  oncontextmenu="return false;">Live Chat </a> We are available 24 hours a day, 7 days a week.',
                'Do you offer yard letters yard signs?' => 'Yes, we do. To place an order, please <b>'. self::generateLink('category', 'browse our yard letters SKUs', ['slug' => 'yard-letters']) .'</b>, which include letters and artwork specifically combined to display a complete message. We also offer bulk discounts on custom messages or yard letters yard signs to showcase specific content. To order, please visit our editor page. On Step 1 you may Choose Your Sizes (inches) and enter the quantity. On Step 2 you may Choose Your Sides (Single or Double). On Step 3 upload your artwork or choose from our templates, then leave a comment of the desired templates as a pack. Once you submit your order we will send a free proof for your review in 1 hour. You may Request Changes if needed before you Approve for Production. For any questions please call ' . self::TEL_LINK . ', email ' . $this->getStoreEmail() . ', or message us on our ' . self::LIVE_CHAT . '. We are available 24 hours a day, 7 days a week.',
                'Can I just order wire stakes?' => 'Yes you can certainly order just wire stakes! Please visit our '. self::generateLink('order_wire_stake', 'wire stakes page') .' to order. You may also contact us by calling ' . self::TEL_LINK . ', messaging us on our ' . self::LIVE_CHAT . ', or emailing ' .  $this->getStoreEmail() . ' if you need any assistance placing an order.',
                'Can I save my design to return and work on it later?' => 'Yes you can save your design prior to submitting an order. Please complete the steps in order on our editor page. Then click the Save Your Design button below the ADD TO CART button. Please enter your email address to save your design. You will immediately receive an email with a link to your design. This link will not expire for 30 days. You may also proceed to checkout and submit your order by choosing See Design - Pay Later. You will not be required to pay until you are ready to approve your proof for production. For questions please call ' . self::TEL_LINK . ', email ' .  $this->getStoreEmail() . ', or message us on our <a href="#" onclick="Tawk_API.toggle(); return false;"  oncontextmenu="return false;">live chat </a>.',
                'Do you print height by width (H x W) or width by height (W x H)?' => 'Our default measurements are indicated as width by height (W x H) for all of our products. You will see this prior to submitting an order on our editor page. We are able to create fully custom sizes, up to 48" width x 96" height or 96" width x 48" height (inches).',
                'How do I create an account?' => 'Please visit ' .self::MY_ACCOUNT. ' to create an account. This will allow you to review all past order details, edit saved designs, access saved carts, and review emailed quotes. You are also able to submit repeat orders from here with ease, view your YSP rewards balance, and much more!',
                'If I have a double-sided sign, how do I make sure the arrows are pointing in the same direction?' => 'By default our Design Team will create and send a proof for your review in 1 hour. This will show both sides of your sign with the arrows pointing in the same direction from either side. You may also leave a comment prior to submitting your order. You are able to Request Changes if needed before you Approve your proof for production. For any questions you may contact us 24 hours a day, 7 days a week by calling ' . self::TEL_LINK . ', emailing ' .  $this->getStoreEmail() . ', or messaging us on our <a href="#" onclick="Tawk_API.toggle(); return false;"  oncontextmenu="return false;">live chat </a>.',
                'How do I request blind shipping?' => 'For third party reselling and drop shipping, we suggest requesting blind shipping. This ensures your order will not contain any packing lists or our company information on the shipping label. Please comment "blind shipping" prior to submitting your order.',
                'Can you deliver on the weekends?' => 'Yes we can! Please choose Saturday delivery. You will see this delivery date option available between Thursday and Friday.',
                'What are Reverse Arrows?' => 'Reverse Arrows are directional arrows used on double-sided yard signs to ensure your customer is directed to the correct location, regardless of which side of the sign they are facing. When designing a sign with Reverse Arrows, you will need to provide both a front and back version of your design, ensuring that the arrows are appropriately reversed on each side for clarity and accuracy. If unavailable, simply leave a comment. Our Design Team will create a proof reflecting this and email you a proof for your review in 1 hour. Once approved, we will begin production.',
                'Can you print in RGB or PMS?' => 'We print using CMYK colors. If your submitted design file is in RGB or PMS we will convert it to CMYK for the best color results. We will match the color as close as possible and share a proof for your review. You can Request Changes if needed prior to Approving your proof for Production. We will ensure the color matches your requirements.',
                'What color grommets do you offer?' => 'We offer grommets in two colors: silver and gold, so you can choose the best match for your yard signs!',
                'How do you handle variable data?' => 'We make it easy to customize your yard signs with variable data. Whether you need to add different names, dates, or other details to multiple signs, we can handle it! Simply upload your design and provide a list of the variable data you\'d like to include. Our design team will take care of the rest, ensuring each sign is printed with the correct information. We will email you a proof for your review in 1 hour after you submit your order. You can request changes on the proof link if needed prior to approving for production.  If you have any questions or need assistance, please do not hesitate to call ' . self::TEL_LINK . ', email ' . $this->getStoreEmail() . ', or message us on our <a href="#" onclick="Tawk_API.toggle(); return false;"  oncontextmenu="return false;">live chat </a>!',
                'How much margin should I leave to avoid cropping or coverage issues?' => 'To ensure no important elements are cut off during production, leave at least 0.25-inch safety margin inside the edges of your design. Avoid placing text or critical elements near the edges. Additionally, extend the background design or color to a 0.125-inch bleed area beyond the trim size to ensure seamless printing.',
                'Are your signs are eco friendly and biodegradable?' => 'Our signs are recyclable. They are not biodegradable at this time. This is due to the polypropylene plastic required to keep the signs durable.',
                'Are double sided coroplast signs made from opaque plastic?' => 'Yes, our double-sided coroplast signs are made from opaque plastic. This helps ensure that the design on the front doesn\'t bleed through to the back, providing clear, distinct visibility on both sides.',
                'How do I place an order with multiple sign designs?' => 'Each unique design is considered a separate item. Simply add each design to your cart individually before proceeding to checkout. You can also email your designs to <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a>. or include a link to your files in the order notes at checkout. For questions please call <a href="tel: +1-877-958-1499">+1-877-958-1499</a>, email <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a>, or message us on our <a href="#" onclick="Tawk_API.toggle(); return false;"  oncontextmenu="return false;">live chat </a>.'
            ],
            'General Information' => [
                'What printing method do you use?' => 'Our standard printing method is ultraviolet (UV) printing. We print your designs directly onto durable, weather resistant, 4mm thick corrugated plastic. The ink is instantly cured onto the corresponding plastic via UV light. This leaves a smooth, seamless, embedded, and vibrant finish. This allows for permanent customizations in full color. This is our most advanced style printing method, and most popular across the industry. We can assure you that we will always use the highest quality printing methods to make sure your outdoor yard signs look great.',
                'What is ' .$this->getStoreName(). ' Order Protection?' => 'We ensure 100% satisfaction with your order. This optional service guarantees delivery, product quality, service, and satisfaction or your money back. If your order is delivered late, we\'ll issue a full shipping refund. If no longer needed, we\'ll take your order back & issue a full refund. If your order is incorrect or damaged, we\'ll exchange it. We\'ll prioritize your concerns & resolve any issue in less than 24 hours. If you\'re not 100% satisfied with your order, we\'ll take it back & issue a full refund. To learn more, please visit <a href="/terms-and-conditions"> Terms & Conditions </a>',
                'What sizes do you offer?' => 'Our standard 10 sizes are the following in inches (width x height): 6”x18”, 6”x24”, 9”x12”, 9”x24”, 12”x12”, 12”x18”, 18”x12”, 18”x24”, 24”x18”, 24”x24”. These are the most common sizes within the industry. The most popular and best selling sizes are 24”x18” and 18”x12” (width x height in inches).',
                'What is the largest size you can print?' => 'The largest size sign we can print is approximately 48 inches by 96 inches (48”x96”, width x height) with a few inches to spare for margins.',
                'Do you offer custom sizes and shapes?' => 'Yes we do! To order custom sizes, please upload your design on our live editor page and leave a comment about your particular measurements (width x height in inches). We can also print our standard designs in any measurements smaller than 48”x96” (width x height in inches). Once you submit your order, we will send a proof for your review in 1 hour. You will be able to Request Changes before you Approve for Production if needed. If you have any questions you may contact us by calling <a href="tel:+18779581499"><a href="tel: +1-877-958-1499">+1-877-958-1499</a></a>, emailing <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a>, or messaging us on our <a href="#" onclick="Tawk_API.toggle(); return false;"  oncontextmenu="return false;">live chat </a>. We are available 24 hours a day, 7 days a week.',
                'Do you produce die-cut yard signs?' => 'Yes we do! To order, please visit our editor page. On Step 1 you may Choose Your Sizes (inches) and enter the quantity. On Step 2 you may Choose Your Sides (Single or Double). On Step 3 upload your artwork or choose from our templates, then leave a comment of the desired dimensions. Once you submit your order we will send a free proof for your review in 1 hour. You may Request Changes if needed before you Approve for Production. For any questions please call <a href="tel: +1-877-958-1499">+1-877-958-1499</a>, email <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a>, or message us on our ' . self::LIVE_CHAT . '. We are available 24 hours a day, 7 days a week.',
                'What is the thickness of your metal wire frames (stakes)?' => 'Our standard metal wire frames or stakes are 3.4 mm thick, our premium wire stakes are 5 mm thick.',
                'What is the gauge (diameter) of your metal wire frames (stakes)?' => 'Our metal wire frames or stakes are 10 gauge (wire diameter).',
                'What is the weight of your metal wire frames (stakes)?' => 'Our standard metal wire frames or stakes weigh 0.14kg, our premium wire stakes weigh 0.23kg.',
                'Do your signs include corrugated holes or flutes used to insert wire stakes?' => 'Yes all of our signs include corrugated holes or flutes along the top and bottom edges. All of our signs can be used with wire stakes. This allows for easy and instant installation of wire stakes to hold and display your yard signs.',
                'What kind of grommets do you use?' => 'All of our grommets are 3/8 inch in size. They are resistant to rust and corrosion, ensuring long-lasting durability even in harsh weather conditions. They are an excellent choice for signs needing to be hung or tied down.',
                'Where do you put grommets?' => 'If grommet placement is not specified in the design file, we will install the grommets 1" from the edges of your sign. If you do provide specifications, please include small 3/8" dots in your design file and leave a note to inform us. We generally recommend placing grommets 1" from the edges of your sign. You can also choose the placement of your grommets on our '. self::generateLink('custom_yard_sign_editor', 'order page', ['variant'=>'24x18']) .' on the “Choose Your Grommets (3/8 Inch Hole)" step.',
                'What is the most popular size?' => 'The most common size is '. self::generateLink('custom_yard_sign_editor', '24x18', ['variant'=>'24x18']) .' (width x height in inches) which is versatile and can be seen from afar (including from street view). We also offer custom shapes and sizes. Other popular sizes include: <ul><li>'. self::generateLink('custom_yard_sign_editor', '18x12', ['variant'=>'18x12']) .': Good for short messages and simple logos.</li><li> '. self::generateLink('custom_yard_sign_editor', '18x24', ['variant'=>'18x24']) .': Provides more space for contact details or a call to action.</li><li>'. self::generateLink('custom_yard_sign_editor', '24x24', ['variant'=>'24x24']) .':  Recommended for promotions that need to share more information, and can be seen from further distances.</li><li>'. self::generateLink('custom_yard_sign_editor', '12x18', ['variant'=>'12x18']) .': A smaller sign that goes with your main sign, and is good for contact numbers and sale status updates.</li></ul>',
                'What is the recommended pixel count?' => 'For high quality prints we suggest at least 150 DPI, and 7200px by 7200px or higher.',
                'Do signs come with stakes?' => 'Stakes are not included by default. You’ll need to select the stake option when placing your order—choose from no stakes, standard wire stakes, single stakes, or heavy-duty stakes. For more information, visit our ' . self::generateLink('order_wire_stake', 'wire stakes page') . ', or add stakes to your order directly from our ' . self::generateLink('homepage', 'Order page') . '. For questions please call <a href="tel: +1-877-958-1499">+1-877-958-1499</a>, email <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a>, or message us on our <a href="#" onclick="Tawk_API.toggle(); return false;"  oncontextmenu="return false;">live chat </a>.'
            ],
            'Artwork' => [
                'Can I put a logo or emblem on my sign?' => 'Yes you can upload your own logo. We can also suggest logos or emblems for you.',
                'Why is the color of the actual product not exactly the same as the proof I approved?' => 'The colors of the proof as shown are approximate and will differ on each computer monitor or mobile screen. However, the color shown on the proof and actual product will be nearly identical.',
                'Can I use another font, not the default font in your proofs?' => 'Yes, you can suggest a special font for us to use on your signs. The style of all lettering and numbers can be customized to your choosing.',
                'Does white count as an imprint color?' => 'White is not considered an imprint color. There is no additional charge for this. Your order includes one imprint color at no additional cost.',
                'What does imprint color mean?' => 'Imprint colors refer to the different colors present on your design or artwork. We omit and do not charge for white. Please Choose Imprint Color according to the total colors present in your design or artwork (excluding white).',
                'Can I order multiple signs with different designs but the same sizes?' => 'Yes! To order, please visit our editor page. On Step 1 you may Choose Your Sizes (inches) and enter the quantity. On Step 2 you may Choose Your Sides (Single or Double). On Step 3 upload all artwork or designs. Then leave a comment that you have different designs. Once you submit your order we will send a free proof for your review in 1 hour. You may Request Changes if needed before you Approve for Production. For any questions please call <a href="tel: +1-877-958-1499">+1-877-958-1499</a>, email <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a>, or message us on our ' . self::LIVE_CHAT . '. We are available 24 hours a day, 7 days a week.',
                'Can you match the color of any design?' => 'Our printers use CMYK color. If your design is in RGB, our design team will work to match the colors as closely as possible.',
                'What resolution is recommended for custom design files?' => 'For best results, we recommend submitting design files with a resolution of at least 150 DPI (dots per inch) at the actual size of the yard sign. This ensures crisp, high-quality printing without pixelation. If you’re unsure about your file quality, please email us at  <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a> , call ' . self::TEL_LINK . ', or message us on our ' . self::LIVE_CHAT .'.',
                'What color format should I use for my design files?' => 'We use the CMYK color format for printing, as it provides the most accurate color reproduction. If your design is in RGB on Pantone (PMS), we’ll convert it to CMYK, which may result in slight color variations. To ensure color accuracy, please provide CMYK color codes for any specific brand colors you would like us to match.',
            ],
            'Digital Proof' => [
                'Can I see a proof before I purchase?' => 'After placing an order, we will send you a free proof of what your order will look like. If you would like to make any revisions to the proof, this is free of charge. We will make as many revisions to your proof as you would like. Once you are 100% satisfied and approve of the proof, we will proceed with processing your order. If you would like, you may cancel your order and request a full refund at any time before we process your order.',
                'Will I be charged extra if I want to change the proof before confirming the order?' => 'No, you may make as many changes to the proof as you would like.',
                'I received my proof and I want to make a change. How can I do that?' => 'Simply reply back to the emailed proof that you received and tell us the change you would like to make. We will revise and send you a new proof. You can also click Request Changes on your proof link and submit your revision.',
                'How long does it take to get a digital proof?' => 'Our average turnaround time is 1 hour.',
                'What is a digital proof?' => 'A digital proof is a digital image or design of how your sign will look. We will show you an image before we start production. You can make as many changes as you like to the digital proof until you are completely satisfied.',
                'How can I confirm the proof I received?' => 'Simply reply to the proof email stating that you confirm the proof layout and then we will start production, or approve your proof via the proof link.',
                'How do I ensure everything will be properly centered?' => 'Our expert Designers will center align your text, numbers, and / or artwork by default. We will ensure that your designs are properly aligned. You will be able to reference alignment and positioning on the digital proof you receive after you submit your order. You may request as many changes as you would like until your proof is perfect and ready to approve. If we have any questions, we will always contact you to make sure your designs are properly aligned before we begin production.',
                'Does all text, numbers and/or artwork (customizations) auto align for each individual sign?' => 'Yes! By default our Design Team will center align all customizations added to your order. You may leave special instructions or comments prior to submitting your order. All comments will be reviewed by our Design Team and applied to your order. Our Design Team will create and send a proof for your review in 1 hour after your order is submitted. You are able to Request Changes if needed before you Approve your proof for production. For any questions you may contact us 24 hours a day, 7 days a week by calling '.self::TEL_LINK.', emailing ' . $this->getEmailLink() .', or messaging us on our '.self::LIVE_CHAT.'.'
            ],
            'Refunds / Cancellations' => [
                'What if I need to cancel my order?' => 'You may cancel your order free of charge and receive a full refund if your order is not in production. If the order is already in production, then your order cannot be canceled due to the production and material cost that we have already incurred.',
                'What should I do if I made a payment twice by mistake?' =>'Please call our sales team at <a href="tel:+1-877-958-1499">+1-877-958-1499</a> or email them at ' . $this->getEmailLink() . '.',
                'What happens if there is a mistake with my order?' => 'If there is a production error please email us pictures of your order at '. $this->getEmailLink().' and we will investigate the concern.',
                'When will I receive my refund?' => 'If you qualify for a refund and we have already issued the refund to you, the refund will be completed within 1-5 business days. This will reflect on your payment account. However, the refund may be processed before this. If you do not see a refund within this time frame, please contact your payment method provider.'
            ],
            'Payment Information' => [
                'What methods of payment are accepted?' => 'We accept the following credit cards: Mastercard, Visa, Discover, and American Express. You may also pay with PayPal. The method to pay by check is also available for customers who wish to mail in a check. Purchase orders are accepted for schools, certain non-profit groups, and government organizations.',
                'Do you charge sales tax?' => 'We do not charge any tax. We also do not charge any other fees.',
                'How do I place an order if I am a school with a purchase order (PO)?' => 'Visit the order now page and follow the steps to place an order with your specification. Then once you are at the checkout page, enter your shipping and billing information. Below this there will be an option to pay by "Check / PO." Click this option and select submit. You will receive an order number by email. Place this order number on your school’s official PO and email this to <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a>.',
                'How is pricing determined for sign orders?' => 'Pricing is based on your selected options, including size, quantity, sides, colors, stakes, and more. You’ll see updated pricing as you configure your order on our site. For questions please call <a href="tel: +1-877-958-1499">+1-877-958-1499</a>, email <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a>, or message us on our <a href="#" onclick="Tawk_API.toggle(); return false;"  oncontextmenu="return false;">live chat </a>.'
            ],
            'Shipping & Production' => [
                'What is the freight?' => 'The shipping cost will be provided to you on our editor or order page. The shipping cost varies according to the quantity and desired delivery time of your order. You may select your shipping option at the bottom of our editor or order page. We offer the following shipping options: FedEx Next Day, FedEx 2 Day, FedEx Home Delivery, USPS Priority, and USPS First Class. We offer free shipping for all orders over $50+ in value.',
                'Do you ship to APO / FPO addresses, or to a PO Box?' => 'We ship to all addresses including APO, FPO, and PO Boxes. We suggest shipping to physical addresses for the quickest turnaround time.',
                'How long will it take before I receive my order?' => 'After approving the proof we send you, our standard turnaround time is 1 to 5 business days including production and delivery depending on the delivery date you select.',
                'How accurate is your delivery date?' => 'Almost all of our packages are delivered to our customers on the date specified on their order. For delivery delays, we will prorate any shipping fees you paid for expedited shipping. Shipping may be delayed if the approval process for the proof we send you exceeds 1 business day, is approved after 4pm CST, or the carrier has delayed your shipment.',
                'Which shipping carrier do you use to ship?' => 'Our primary shipping carriers are UPS, FedEx, and USPS depending on your address and order. For PO Box addresses we typically use USPS.',
                'Are there any extra shipping fees if my order is to be delivered outside the United States?' => 'We ship domestically and internationally. We deliver orders worldwide. Due to international shipping, handling, and customs costs, we charge $75 to ship to countries outside the United States. This also applies to the states of Hawaii and Alaska.',
                'If I place one order but ship to multiple addresses, how are shipping charges applied?' => 'Each delivery address is treated as a separate shipment. Shipping charges are applied for each delivery address. We charge $15 per additional address when shipping to multiple addresses. We recommend consolidating items going to the same address and limited the number of additional shipping addresses.'
            ],
            'Tracking' => [
                'How can I track my order?' => 'You may call us at <a href="tel: +1-877-958-1499">+1-877-958-1499</a>, email us at <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a>, or message us on our <a href="#" onclick="Tawk_API.toggle(); return false;"  oncontextmenu="return false;">live chat </a> and our sales team will give you an update on your order. Once your order has shipped, you will receive a tracking number.',
                'Will I get a tracking number once my order has shipped?' => 'Once your order is shipped, you may contact us by calling <a href="tel: +1-877-958-1499">+1-877-958-1499</a>, emailing <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a>, or messaging us on our <a href="#" onclick="Tawk_API.toggle(); return false;"  oncontextmenu="return false;">live chat </a> and our sales team will send you your tracking number.'
            ],
            'Special Request' => [
                'Can my order be split between different colors, shapes, and sizes?' => 'We can split an order across different colors, shapes, and sizes for the same style. Our website already takes this into effect and offers you the lowest price possible.',
                'Could I order two different logos in the one batch of signs?' => 'Yes absolutely. Simply upload your logos and in the notes section, mention which logos should be assigned to which signs and where the placement of the logos should be.',
                'How do I customize my sign?' => 'Please go to the order now page and follow the steps to customize your signs. You may also contact us at <a href="tel: +1-877-958-1499">+1-877-958-1499</a>, our <a href="#" onclick="Tawk_API.toggle(); return false;"  oncontextmenu="return false;">live chat </a>, or emailing <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a>. if you need any assistance placing an order.',
                'How do I specify where I want text, numbers, and logos to be placed?' => 'On the order page for your products, please add to the Comments section where you would like your customizations to be placed. If you leave the Comments section blank, our Design Team will place your customizations where they think they would look the best based on their expertise. However, we are more than happy to revise the proof we send you if you are not satisfied with the way it looks before processing your order.'
            ],
            'Discounts' => [
                'Can I get a discount?' => 'Our prices are always the lowest in the market for custom signs. We can assure you that there will be no company in the market that will be able to provide a lower price and faster turnaround time than our company. Depending on the size of your order, we may be able to offer additional discounts. Special coupons may be available at the time of purchase.',
                'Do you offer discounts for larger orders / or non-profit groups?' => 'Our prices are always the lowest in the market for custom signs. We can assure you that there will be no company in the market that will be able to provide a lower price and faster turnaround time than our company. Depending on the size of your order, we may be able to offer additional discounts.',
                'Do you price match with other companies?' => 'We do not price match with other companies. However, we can assure you that our prices are always the lowest in the industry for custom signs.',
                'Are coupon codes limited by the number of items I order?' => 'Coupon codes are applicable to any order, based on the quantity specified in the offer terms. You can use them on any number of items as long as the minimum quantity requirement is met.',
                'What coupon codes are available, and what do they offer?' =>
                    'SAVE10 – 10% off, up to $100 in savings (minimum 1 item) <br>
                    BULK12SAVE – 12% off, up to $120 in savings (minimum 250 items) <br>
                    GRAND15SAVE – 15% off, up to $150 in savings (minimum 500 items) <br>
                    ULTRA20SAVE – 20% off, up to $200 in savings (minimum 1000 items)',
                'Can I use more than one coupon code on the same order?' => 'Only one coupon code can be used per order.',
                'Can I add a coupon code after placing my order?' => 'Coupon codes must be applied during checkout. If you were unable to, please contact us prior to approving your proof. We will apply the coupon code for you. Please note once your order is approved for production we are unable to make any further changes including applying coupons or discounts.',
            ],
            'Other Questions' => [
                'How can I trust your company?' => 'We have been in the online customization business for multiple years. We have been the leading provider in custom signs with many satisfied customers. Also, we promote paying by credit card or PayPal so that if there is any issue with your order, you may simply dispute the order with your credit card company or PayPal account to ensure your payment will be secured. However, we can assure you that we will assist you with your order if any error occurs on our end.',
                'Are there any hidden fees?' => 'We do not have any hidden fees. Our company is transparent on exactly what we charge to our customers. Your total will be displayed to you multiple times throughout the checkout process before you confirm your order. You will not be charged anything besides what your order total states.',
                'Do you deliver outside of the United States?' => 'Yes, we ship our products worldwide.',
                'How much does one sign weigh?' => 'The weight varies based on the size of the sign. However, the average weight for 1 sign is under 0.2 lbs.',
                'Are your signs waterproof and outdoor resistant?' => 'Yes, all of our signs are waterproof, sun resistant, snow resistant, and overall weather resistant. This includes our full-color printing. Your customizations will not fade from any outdoor weather condition.',
                'Can your signs be used with a wire stake?' => 'Yes, all of our signs can be used with wire stakes (H-Stakes) as our signs include corrugated holes or flutes along the top and bottom edges. This allows for easy and instant installation of wire stakes. We offer 10"W x 24"H wire stakes for all sizes except 6”W x 18”H, 6”W x 24”H, 9”W x 12”H, 9”W x 24"H signs (our smaller sizes).',
                'Are the signs recyclable?' => 'Yes, most of our yard signs are recyclable! They are typically made from corrugated plastic (also known as polypropylene), which is classified as recyclable. To recycle your signs remove any metal stakes before recycling. Check with your local recycling facility to ensure they accept polypropylene materials.',
                'What is the lifespan of your signs?' => 'We estimate that our signs will withstand all outdoor weather conditions for over 1 to 2 years. If covered or used indoors, our custom outdoor yard signs will stay in great condition for long-term use.',
                'How do I install the wire stake?' => 'Installation of wire stakes is effortless. Simply insert the wire stake directly into the corrugated holes on the edges of the yard sign. Then place your wire stake in any patch of grass or dirt for support.',
                'Do I have to pay import fees?' => 'For orders within the US, no import fees are implemented by the shipping couriers we use. For orders outside of the US, your shipping address may be subject to incur an import fee by shipping couriers. Any import fees will be reimbursed. This includes orders shipped to Canada. Please pay the import fees, then simply email us a copy of the receipt. We will refund you the import fees incurred immediately. We will credit the payment method used for your order within the same day.',
                'Can I use copyrighted logos?' => 'Please note we do not take any responsibility or ownership for permission to use, reproduce, or apply logos, trademarks, or copyright information. We are not held liable for responsibility for obtaining permission for any copyrights. By agreeing to print any information submitted, we will not be held liable for any copyright on infringement concerns under any circumstance.',
                'What is the cost of a yard sign?' => 'To receive a quote, please simply follow the Steps on our editor page. Alternatively, you can ping us on our ' . self::LIVE_CHAT . ', call us at <a href="tel: +1-877-958-1499">+1-877-958-1499</a>, or email us at <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a>.. The price varies depending on the quantity, sizes, and customizations selected. The more you order, the lower the price. You can customize your order using our online live editor tool. Once your order is submitted, we will email you a proof within one hour for you to approve for production or request changes if needed.',
                'Which shipping carrier do you use to ship?' => 'Our primary shipping carriers are UPS, FedEx, and USPS depending on your address and order. For PO Box addresses we typically use USPS.',
                'What is the size of your bleed line?' => 'Our bleed is approximately +3mm on each side to ensure your design is printed in full on the sign. We suggest to allow up to 1 inch along the borders. You will receive a proof for your review after you submit your order. You may Request Changes if needed before you Approve for Production.',
                'What is the size of the corrugated holes or flutes on the plastic sheets?' => 'The size of the corrugated holes, internal channels, or known as flutes included with all of our plastic sheets is a approximately 3/16 inch wide. These are internal openings that run vertically or horizontally (depending on orientation) through the sign, allowing wire stake frames to be inserted. The spacing between the flutes is consistent throughout the sign, and the exact width is tailored to ensure a snug fit for the stakes to hold the sign securely.',
            ],
            'YSP Rewards' => [
                ...self::REWARDS_FAQS['How it works'],
            ]
        ];
    }

    const REWARDS_FAQS = [
        'How it works' => [
            'What are YSP Rewards?' => 'YSP Rewards are credits that can be used towards your next purchase to discount the order total. It’s our way of thanking you for submitting a repeat order!',
            'How many YSP Rewards will I earn on my orders?' => 'You will earn 5% of the order total (excluding shipping) on all purchases. Your order total includes any coupons or existing YSP Rewards applied. The maximum YSP Rewards redeemable for a given order is $50.00.',
            'What is the maximum YSP Rewards I can apply towards an order?' => 'The maximum amount of YSP Rewards you can apply towards one order is $50.',
            'Can I spend my YSP Rewards immediately?' => 'YSP Rewards will be available for use once the previous order has been shipped. You will see your YSP Rewards balance in your ' .self::MY_ACCOUNT. '.',
            'How are YSP Rewards calculated?' => 'YSP Rewards are calculated based on the cost of your previous order, excluding shipping.',
            'How do I signup?' => 'You are automatically eligible to use your YSP Rewards once you complete an order. Your YSP Rewards balance will be made available once your previous order has shipped. Create a ' .self::MY_ACCOUNT. ' to begin using. Please sign up with the same email address used for your previous order. All completed orders from July 9, 2024 onwards are eligible.',
            'How do I use my YSP Rewards?' => 'You can use your YSP Rewards on any custom order when you have a YSP Rewards balance. Login to your ' .self::MY_ACCOUNT. '. Then Add to Cart your order. The Shopping Cart and Checkout page will automatically apply your YSP Rewards balance as a credit on your order total.',
            'Do YSP Rewards expire?' => 'YSP Rewards expire 6 months after the initial purchase used to generate the balance. You can review your YSP Rewards balance from your ' .self::MY_ACCOUNT. '.',
        ]
    ];

    public function getMembershipFaqs(): array
    {
        return [
            'Frequently Asked Questions' => [
                'How long will it take for the discount code to be activated?' => 'You can email us at ' . $this->getEmailLink().' to get your special discount code. Please note that only one discount or promotional code will be accepted upon checkout.',
                'How many times can I use the discount code?' => 'You can use the discount code as many times as you want, no minimum purchase or order quantity is required.',
                'Can I use the discount code for all ' .$this->getStoreName(). ' products?' => 'The discount applies to all on-hand and new-arrival products. However, the discount code cannot be combined with other  <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a> deals and promotions.'
            ]
        ];
    }

    public function getTeacherDiscountFaqs(): array
    {
        return [
            'Frequently Asked Questions' => [
                'How long will it take for the discount code to be activated?' => 'You can email us at ' . $this->getEmailLink() .' to get your special discount code. Please note that only one discount or promotional code will be accepted upon checkout.',
                'How many times can I use the discount code?' => 'You can use the discount code as many times as you want, no minimum purchase or order quantity is required.',
                'Can I use the discount code for all ' .$this->getStoreName(). ' products?' => 'The discount applies to all on-hand and new-arrival products. However, the discount code cannot be combined with other <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a> deals and promotions.'
            ]
        ];
    }
    
    public function getHealthcareWorkersAndFirstResponderFaqs(): array
    {
        return [
            'Frequently Asked Questions' => [
                'How can I receive the discount?' =>
                'If interested, send us an email at ' . $this->getEmailLink() . ' to get your discount code. Be sure to include your name and verifiable'."<b> employment information </b>".'in your original email so our team can ensure this discount is received by medical and healthcare professionals only.' . "<br><br>" .
                    'Please take note that only one coupon code will be accepted upon checkout. The discount will automatically be applied after entering it on the shopping cart page. Should it not reflect on your end, send us an email to ' . $this->getEmailLink() . ' and we will be happy to assist you.',
                'How many times can I use the discount code?' => 'You can use the discount code as many times as you would like, with no minimum purchase requirement.',
                'Is free shipping available when I use the discount code?' => 'We offer free shipping on orders $50 or more. You will see this before you Add to Cart.',
                'Can I use the discount code for all ' .$this->getStoreName(). ' products?' => 'The discount is applicable to all on-hand and new arrival products. However, the discount code cannot be combined with any other'." <b> " .$this->getStoreName()."  </b>".'coupon codes.'
            ]
        ];
    }

    public function getMilitaryVeteransFaqs(): array
    {
        return [
            'Frequently Asked Questions' => [
                'How can I receive the discount?' => 'You can email us at ' .  $this->getStoreEmail() . ' to get your special discount code. Please note that only one discount or promotional code will be accepted upon checkout.',
                'How many times can I use the discount code?' => 'You can use the discount code as many times as you would like, with no minimum purchase required.',
                'Can I use the discount code for all ' .$this->getStoreName(). ' products?' => 'The discount applies to all on-hand and new-arrival products. However, the discount code cannot be combined with other <a href="mailto:' . $this->getStoreEmail() . '">' . $this->getStoreEmail() . '</a> deals and promotions.'
            ]
        ];
    }

    public function getRewardsFaqs(): array
    {
        return self::REWARDS_FAQS;
    }

    public function getConstants()
    {
        return [
            'FAQS' => self::getFaqs(),
            'FAQS_TITLE' => self::FAQS_TITLE
        ];
    }

    public function generateUrlAbsoluteUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): string
    {
        return $this->generateUrl($route, $parameters, $referenceType);
    }

    public function generateAnchorTag(string $url, string $text): string
    {
        return sprintf('<a href="%s" target="_blank" >%s</a>', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'), htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    }

    public function generateLink(string $route, string $displayText, array $parameters = []): string
    {
        $url = $this->generateUrlAbsoluteUrl($route, $parameters);
        return $this->generateAnchorTag($url, $displayText);
    }

    public function getStoreName(): string
    {
        return $this->storeInfoService->storeInfo()['storeName'] ?? 'Yard Sign Plus';
    }

    public function getStoreEmail(): string
    {
        return $this->storeInfoService->storeInfo()['storeEmail'] ?? 'sales@yardsignplus.com';
    }

    private function getEmailLink(): string
    {
        $email = $this->storeInfoService->storeInfo()['storeSupportEmail'] ?? 'sales@yardsignplus.com';
        return '<a href="mailto:' . $email . '">' . $email . '</a>';
    }

}