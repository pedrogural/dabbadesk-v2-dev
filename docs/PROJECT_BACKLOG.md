DABBA Desk Project Backlog
🔴 Bugs
Draft Orders
☐	“Saved” confirmation box sometimes remains on screen.
☐	After performing an action, the review panel background disappears.
________________________________________
🟡 UX Improvements
Orders
Order Index
New filters required:
☐	Purchase Incomplete (includes partially paid, awaiting purchase)
☐	Paid (all fully paid orders)
☐	Unpaid (active orders awaiting payment)
________________________________________
Purchases
☐	“My Orders” checkbox to show only orders assigned to me.
☐	Display retailer delivery fee allocation per item (especially marketplace sellers).
☐	Review retailer header statistics spacing.
☐	Review purple badge sizing and spacing.
☐	Continue typography refresh using Purchases as the reference module.
________________________________________
🔵 Functional Enhancements
Purchases
Post Purchase Resolution
Example:
Customer ordered Qty 2
Operator accidentally purchased Qty 1
Item arrives and is marked as arrived.
CMS must allow creation of a Post Purchase Resolution which:
•	keeps the arrived item in history
•	creates a replacement purchase requirement for the missing quantity
•	links both purchases together
•	keeps a full audit trail
________________________________________
Purchase Problems
Pre-purchase problems
•	Out of stock
•	Awaiting customer approval
•	Price change
•	Supplier unavailable
Post-purchase problems
•	Wrong item ordered (Dabba error)
•	Wrong quantity ordered (Dabba error)
•	Supplier sent wrong item
•	Damaged item
•	Missing item
•	Courier damage
•	Replacement purchase required
________________________________________
🟣 UI Refresh Programme
Purchases will become the reference module for the new DABBA Desk design language.
Remaining modules to refresh:
☐	Dashboard
☐	Orders
☐	Order Requests
☐	Draft Orders
☐	Customer Desk
☐	Money Desk
Reuse:
•	lighter typography
•	modern confirmation modals
•	sticky action cards
•	collapsible panels
•	consistent spacing
•	white backgrounds
•	softer colour palette
•	no browser popups

Refactor attachment handling into a shared AttachmentService.

Store the filesystem disk with each attachment.
Eliminate path guessing.
Reuse across all DabbaDesk modules.