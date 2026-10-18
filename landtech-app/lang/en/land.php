<?php

/*
|--------------------------------------------------------------------------
| LandTech 2026 — Citizen land workflow (English, source of truth)
|--------------------------------------------------------------------------
|
| Every key here MUST exist in bn/land.php — MissingTranslationsTest enforces
| parity in both directions.
|
| WORDING RULES FOR THIS FILE
|   - Never imply a submission proves ownership.
|   - Never imply a verification is official or government-backed.
|   - Never imply a listing is a legal transfer of title.
|   - Always name the limitation alongside the result.
|
*/

return [

    // ================= MODE SELECTION =================
    'mode' => [
        'label' => 'Panel mode',
        'seller' => 'Landowner / Seller',
        'buyer' => 'Land Buyer',
        'switch_button' => 'Switch',
        'switched' => 'Panel mode changed. Your account permissions are unchanged.',
    ],

    'modes' => [
        'title' => 'Citizen Panel',
        'subtitle' => 'Choose how you want to work with land records',
        'welcome' => 'Welcome, :name',
        'active' => 'Currently selected',
        'enter_seller' => 'Continue as Landowner / Seller',
        'enter_buyer' => 'Continue as Land Buyer',

        'seller_title' => 'Landowner / Seller',
        'seller_desc' => 'Record and manage land you claim, upload supporting documents, and offer a share for sale.',
        'buyer_title' => 'Land Buyer',
        'buyer_desc' => 'Search published listings and request an ownership verification for any land, listed or not.',

        'link_verified' => 'Identity linked and verified',
        'link_pending' => 'Identity link awaiting review',
        'link_absent' => 'No verified identity link',
        'link_explainer' => 'Your account is not yet linked to a verified land-owner identity. You can still submit land records and documents, but nothing will be attributed to a party until a reviewer approves the link.',

        'card_land_records' => 'My land records',
        'card_pending_verification' => 'Awaiting verification',
        'card_published_listings' => 'Published listings',
        'card_open_verifications' => 'Open verification requests',

        'mode_notice' => 'These two modes are views of the same Citizen account. Switching between them does not change your permissions, grant any official role, or give access to registry, bank or administrative functions.',
    ],

    // ================= LAND RECORDS =================
    'land' => [
        'title' => 'My Land',
        'subtitle' => 'Land records you have submitted',
        'add' => 'Add New Land',
        'add_subtitle' => 'Record land you claim. Submitting a record does not prove ownership.',
        'edit' => 'Edit land record',
        'empty' => 'You have not submitted any land records yet.',
        'empty_hint' => 'Add a record to keep your own land information in one place. Nothing is verified automatically.',

        'status_label' => 'Status',
        'reference' => 'Reference',
        'location' => 'Location',
        'khatian_dag' => 'Khatian / Dag',
        'area' => 'Area',
        'share' => 'Claimed share',
        'basis' => 'Ownership basis',
        'intent' => 'Purpose',
        'submitted_on' => 'Submitted on',
        'updated_on' => 'Last updated',
        'documents_count' => 'Documents',

        'section_location' => 'Location',
        'section_identifiers' => 'Land identifiers',
        'section_ownership' => 'Claimed ownership',
        'section_deed' => 'Deed reference',
        'section_mutation' => 'Mutation',
        'section_intent' => 'Purpose of this record',

        'field_district' => 'District',
        'field_upazila' => 'Upazila',
        'field_mouza' => 'Mouza',
        'field_jl_no' => 'JL number',
        'field_mouza_select' => 'Select a mouza (optional)',
        'field_mouza_help' => 'Choose a mouza from the list, or type the location below if it is not listed.',
        'field_survey_type' => 'Survey type',
        'field_khatian_no' => 'Khatian number',
        'field_dag_no' => 'Dag / plot number',
        'field_area' => 'Total area',
        'field_area_unit' => 'Unit',
        'field_share_numerator' => 'Share numerator',
        'field_share_denominator' => 'Share denominator',
        'field_share_help' => 'Enter your claimed share as an exact fraction, for example 1/3 for a one-third share. Do not assume you own the whole plot.',
        'field_ownership_basis' => 'Ownership basis',
        'field_ownership_basis_other' => 'Describe the ownership basis',
        'field_deed_no' => 'Deed number',
        'field_deed_date' => 'Deed date',
        'field_registration_office' => 'Registration office',
        'field_mutation_status' => 'Mutation status',
        'field_mutation_no' => 'Mutation number',

        'save_draft' => 'Save as draft',
        'submit_review' => 'Submit for verification',
        'submit_confirm' => 'Submit this record for verification? You will not be able to edit it while it is under review.',
        'request_correction' => 'Request a correction',
        'correction_note' => 'What needs to be corrected?',
        'delete_draft' => 'Delete draft',
        'delete_confirm' => 'Delete this draft permanently?',

        'claim_warning' => 'This record is your own statement. It has not been checked against any official register and does not by itself establish ownership.',
        'no_verified_link_note' => 'No verified identity link is attached, so this record is not attributed to any party. A reviewer must approve your identity link first.',

        'draft_saved' => 'Draft saved. Nothing has been submitted for verification yet.',
        'submitted' => 'Record submitted. It is now awaiting verification — it is not verified.',
        'submitted_for_review' => 'Record submitted for verification.',
        'correction_requested' => 'Correction requested. A reviewer will look at your note.',
        'draft_deleted' => 'Draft deleted.',

        'intent_record_keeping' => 'Personal record keeping',
        'intent_sale_listing' => 'Intend to list for sale',

        'basis_purchase' => 'Purchase',
        'basis_inheritance' => 'Inheritance',
        'basis_gift_heba' => 'Gift / Heba',
        'basis_court_decree' => 'Court decree',
        'basis_settlement' => 'Settlement',
        'basis_other' => 'Other',

        'mutation_not_done' => 'Not done',
        'mutation_in_progress' => 'In progress',
        'mutation_done' => 'Completed',
        'mutation_unknown' => 'Unknown',
    ],

    // ================= STATUS LABELS =================
    'status' => [
        'draft' => 'Draft',
        'pending_verification' => 'Pending verification',
        'under_review' => 'Under review',
        'additional_information_requested' => 'More information requested',
        'verified' => 'Verified by reviewer',
        'rejected' => 'Rejected',
        'correction_requested' => 'Correction requested',

        'pending_review' => 'Pending review',
        'published' => 'Published',
        'reserved' => 'Reserved',
        'withdrawn' => 'Withdrawn',
        'sold' => 'Marked sold',

        'submitted' => 'Submitted',
        'reviewed' => 'Reviewed',
        'closed' => 'Closed',

        'acknowledged' => 'Acknowledged',
        'contact_shared' => 'Contact shared',
        'site_visit_proposed' => 'Site visit proposed',
        'declined' => 'Declined',
    ],

    // ================= DOCUMENTS =================
    'documents' => [
        'title' => 'My Documents',
        'subtitle' => 'Supporting documents you have uploaded',
        'upload' => 'Upload document',
        'upload_subtitle' => 'Files are stored privately. Only you and an authorized reviewer can open them.',
        'empty' => 'You have not uploaded any documents yet.',
        'empty_hint' => 'Upload deeds, khatian or mutation papers to support a land record. An upload is not proof that a document is genuine.',

        'field_type' => 'Document type',
        'field_file' => 'Choose a file',
        'field_attach_to' => 'Attach to a land record (optional)',
        'field_attach_none' => 'Not attached to a specific record',
        'file_help' => 'Accepted formats: PDF, JPEG, PNG. Maximum size 10 MB.',

        'column_type' => 'Type',
        'column_file' => 'File',
        'column_status' => 'Review status',
        'column_size' => 'Size',
        'column_uploaded' => 'Uploaded',
        'column_record' => 'Land record',

        'download' => 'Download',
        'delete' => 'Delete',
        'delete_confirm' => 'Delete this document? A reviewer will no longer be able to see it.',
        'uploaded' => 'Document uploaded and queued for review.',
        'deleted' => 'Document removed from your list.',
        'upload_failed' => 'The file could not be stored. Please try again.',
        'not_found' => 'That document could not be found.',
        'invalid_type' => 'Only PDF, JPEG and PNG files are accepted, and the file content must match its extension.',

        'privacy_note' => 'Documents are stored outside the public web area and are never served from a public link. Every download is authorized against your account.',
        'evidence_note' => 'An uploaded document is unverified evidence. It does not by itself prove ownership or authenticity.',

        'review_status_pending' => 'Pending review',
        'review_status_approved' => 'Accepted by reviewer',
        'review_status_rejected' => 'Rejected by reviewer',
        'review_status_replacement_requested' => 'Replacement requested',

        'types' => [
            'registered_deed' => 'Registered deed',
            'khatian' => 'Khatian / Porcha',
            'mutation_record' => 'Mutation record (Namjari)',
            'land_tax_receipt' => 'Land development tax receipt',
            'previous_deed' => 'Previous deed (Baya dolil)',
            'inheritance_certificate' => 'Inheritance certificate',
            'mouza_map' => 'Mouza map or survey document',
            'court_order' => 'Court order or dispute document',
            'other' => 'Other supporting document',
        ],
    ],

    // ================= OWNERSHIP LINK =================
    'link' => [
        'heading' => 'Ownership Information',
        'subtitle' => 'Link your account to a verified land-owner identity',
        'claim' => 'Claim an identity link',
        'claim_subtitle' => 'A reviewer must approve this claim before it grants any access.',

        'empty' => 'You have no identity link claims.',
        'empty_hint' => 'Without a verified link, land records cannot be attributed to you. Submitting a claim does not prove ownership.',

        'field_display_name' => 'Name as recorded',
        'field_dob' => 'Date of birth',
        'field_org_reg' => 'Organisation registration number',
        'field_basis' => 'Basis for this claim',
        'field_note' => 'Explain your claim',

        'column_party' => 'Identity',
        'column_status' => 'Status',
        'column_basis' => 'Basis',
        'column_reviewed' => 'Reviewed',

        'status_pending' => 'Awaiting review',
        'status_verified' => 'Verified',
        'status_rejected' => 'Rejected',

        'basis_nid' => 'National ID',
        'basis_birth_registration' => 'Birth registration',
        'basis_inheritance' => 'Inheritance',
        'basis_court_order' => 'Court order',
        'basis_other' => 'Other',

        'submit' => 'Submit claim',
        'withdraw' => 'Withdraw claim',
        'withdraw_confirm' => 'Withdraw this pending claim?',

        'claim_submitted' => 'Claim submitted. It will not grant any access until a reviewer approves it.',
        'claim_updated' => 'Claim note updated.',
        'claim_withdrawn' => 'Claim withdrawn.',
        'already_claimed' => 'You already have a claim for this identity.',
        'no_matching_party' => 'No matching identity record was found. Check the spelling and date of birth, or contact support.',

        'warning' => 'Submitting an identity number or Party ID does not establish ownership. Only a reviewer can approve a link, and only an approved link grants access.',
    ],

    // ================= SALE LISTINGS =================
    'listings' => [
        'title' => 'My Sale Listings',
        'subtitle' => 'Land you have offered for sale',
        'create' => 'Create a listing',
        'create_subtitle' => 'A listing is an advertisement. It is not a transfer of ownership.',
        'edit' => 'Edit listing',
        'empty' => 'You have no sale listings.',
        'empty_hint' => 'You can offer a share of a land record you have submitted. A listing is not published until it passes review.',

        'field_record' => 'Land record',
        'field_title' => 'Listing title',
        'field_description' => 'Public description',
        'field_description_help' => 'Shown publicly. Do not include identity numbers, phone numbers or private document contents.',
        'field_area' => 'Area offered',
        'field_price' => 'Asking price',
        'field_price_unit' => 'Price unit',
        'field_sale_type' => 'Sale scope',
        'field_share_numerator' => 'Share numerator',
        'field_share_denominator' => 'Share denominator',
        'field_contact' => 'Contact preference',

        'sale_full_plot' => 'Whole plot',
        'sale_partial' => 'Part of the plot',

        'contact_via_platform' => 'Through this platform only',
        'contact_phone' => 'Share phone number with interested buyers',
        'contact_email' => 'Share email with interested buyers',

        'save_draft' => 'Save as draft',
        'submit_review' => 'Submit for review',
        'withdraw' => 'Withdraw listing',
        'withdraw_confirm' => 'Withdraw this listing? It will no longer be visible to buyers.',
        'delete' => 'Delete listing',

        'reference' => 'Reference',
        'status_label' => 'Listing status',
        'verification_label' => 'Verification',
        'asking_price' => 'Asking price',
        'interest_count' => 'Enquiries',
        'published_on' => 'Published on',

        'unverified_warning' => 'This listing has NOT been verified. It is an advertisement only and must not be treated as evidence of ownership or as a legally transferable offer.',
        'share_cap_note' => 'You cannot offer more than the share recorded on the land record.',
        'share_exceeds_claim' => 'The offered share is larger than the share claimed on the land record (:claim).',
        'no_eligible_records' => 'You need a land record before you can create a listing.',
        'draft_saved' => 'Draft saved. Nothing is published yet.',
        'submitted_for_review' => 'Listing submitted for review. It is not published yet.',
        'updated' => 'Listing updated.',
        'withdrawn' => 'Listing withdrawn.',
        'deleted' => 'Listing deleted.',
        'published_only_note' => 'Only listings that have passed review appear in buyer search.',

        'verification_unverified' => 'Not verified',
        'verification_pending' => 'Verification pending',
        'verification_document_reviewed' => 'Documents reviewed',
        'verification_verified' => 'Verified by reviewer',
        'verification_rejected' => 'Verification rejected',
    ],

    // ================= BUYER SEARCH =================
    'search' => [
        'title' => 'Find Land',
        'subtitle' => 'Search published sale listings',
        'heading' => 'Search filters',
        'submit' => 'Search',
        'reset' => 'Reset filters',

        'field_district' => 'District',
        'field_upazila' => 'Upazila',
        'field_mouza' => 'Mouza',
        'field_jl_no' => 'JL number',
        'field_khatian_no' => 'Khatian number',
        'field_dag_no' => 'Dag / plot number',
        'field_min_area' => 'Minimum area',
        'field_max_area' => 'Maximum area',
        'field_min_price' => 'Minimum price',
        'field_max_price' => 'Maximum price',
        'field_sale_type' => 'Sale scope',
        'field_verification' => 'Verification status',
        'any' => 'Any',

        'results' => 'Results',
        'result_count' => ':count listing(s) found',
        'empty' => 'No listings match your search.',
        'empty_hint' => 'Only published listings appear here. Try widening the filters, or request an ownership verification for land you already know about.',
        'prompt' => 'Use the filters above to search published listings.',
        'prompt_hint' => 'You can also request a verification for any land, whether or not it is listed here.',

        'view' => 'View listing',
        'share' => 'Share offered',
        'source_label' => 'Source: platform listing',
        'not_official_note' => 'These listings are advertisements submitted by users. They are not official land records and have not been confirmed against any government register.',
    ],

    // ================= VERIFICATION =================
    'verification' => [
        'title' => 'Ownership Verification Requests',
        'subtitle' => 'Request a review of ownership for any land',
        'create' => 'Request ownership verification',
        'create_subtitle' => 'You can request verification for land even if it is not listed here.',
        'empty' => 'You have no verification requests.',
        'empty_hint' => 'A verification request records what could and could not be checked about a piece of land.',

        'need_identifier' => 'Please provide at least a khatian number, dag number or mouza name.',

        'field_district' => 'District',
        'field_upazila' => 'Upazila',
        'field_mouza' => 'Mouza',
        'field_jl_no' => 'JL number',
        'field_survey_type' => 'Survey type',
        'field_khatian_no' => 'Khatian number',
        'field_dag_no' => 'Dag / plot number',
        'field_area' => 'Area (if known)',
        'field_deed_no' => 'Deed reference (if known)',
        'field_registration_office' => 'Registration office (if known)',
        'field_reason' => 'Why do you need this verification?',
        'field_reason_help' => 'This is recorded in the audit trail. Describe the decision you are making.',

        'submit' => 'Submit verification request',
        'submitted' => 'Verification request submitted.',

        'reference' => 'Reference',
        'status_label' => 'Status',
        'land' => 'Land reference',
        'submitted_on' => 'Submitted on',
        'reviewed_by' => 'Reviewed by',
        'reviewed_on' => 'Reviewed on',
        'review_note' => 'Reviewer note',
        'requester_note' => 'Your reason',

        'checks_heading' => 'What was checked',
        'outcome_heading' => 'Outcome',
        'limitations_heading' => 'What this verification does NOT establish',

        'check_result_pass' => 'Consistent',
        'check_result_fail' => 'Conflict found',
        'check_result_inconclusive' => 'Inconclusive',
        'check_result_not_available' => 'Not available',
        'check_result_pending' => 'Pending',

        'official_not_available' => 'Official verification not available',
        'official_pending' => 'Official verification pending',
        'official_note' => 'This project has no connection to any government land, registration or court system. Nothing on this page is an official verification.',

        'risk_label' => 'Risk indicator',
        'risk_note' => 'This is a warning indicator based on our own records only. It is not a finding of fraud and must not be treated as proof of anything.',
        'risk_low' => 'Low signal',
        'risk_moderate' => 'Some signals present',
        'risk_elevated' => 'Elevated signals present',

        'report_further' => 'Request further review',
        'report_conflict' => 'Report conflicting information',
        'report_note' => 'If you believe this outcome is wrong, or you have information about a conflicting claim, contact the registry with this reference number.',

        'additional_info' => 'Supply additional information',
        'additional_info_field' => 'Additional information',
        'additional_info_submit' => 'Send to reviewer',
        'info_supplied' => 'Additional information recorded.',
        'checks_refreshed' => 'Internal checks refreshed.',

        'reviewer_pending_title' => 'Awaiting reviewer action',
        'reviewer_pending_body' => 'This request has been recorded and the internal checks have run. A human reviewer must now assess it. The registry officer review interface is not implemented in this build, so no reviewer action can be taken yet.',

        'detail' => [
            'no_plot_match' => 'No plot in our records matches these identifiers.',
            'plot_identifiers_agree' => 'A plot in our records matches these identifiers.',
            'plot_mouza_conflict' => 'A plot matches the khatian and dag, but the mouza differs.',
            'no_deed_supplied' => 'No deed reference was supplied, so this could not be assessed.',
            'deed_referenced_by_records' => 'This deed number appears on :count record(s) we hold.',
            'deed_not_in_records' => 'This deed number does not appear in any record we hold.',
            'no_plot_linked' => 'No plot in our records is linked to this reference.',
            'no_ownership_rows' => 'No ownership rows are recorded for this plot.',
            'shares_sum_to_whole' => 'Recorded ownership shares sum to a whole.',
            'shares_do_not_sum' => 'Recorded ownership shares do not sum to a whole (total :sum).',
            'ownership_rows_found' => ':count ownership row(s) recorded for this plot.',
            'no_ownership_history' => 'No ownership history is recorded for this plot.',
            'no_duplicate_listings' => 'No other published listing claims this land.',
            'multiple_live_listings' => ':count published listings claim this same land — this is a multiple-sale indicator.',
            'external_source_required' => 'This check needs an official source that is not connected to this system.',
            'unknown_check' => 'This check is not implemented.',
            'insufficient_input' => 'Not enough information was supplied to run this check.',
        ],

        'limitation' => [
            'internal_records_only' => 'Checked against this platform\'s own records only.',
            'no_registry_access' => 'No connection to any registration authority; the deed could not be verified.',
            'history_incomplete' => 'Our ownership history may be incomplete.',
            'published_listings_only' => 'Only listings published on this platform were compared.',
            'indicator_not_proof' => 'An indicator only. Not proof of fraud.',
            'no_external_integration' => 'No integration with the relevant authority exists in this build.',
            'more_information_needed' => 'More information is needed before this can be assessed.',
            'overall' => 'This verification used only records held by this platform and the information you supplied. No government land, registration, tax or court system was contacted, because no such integration exists here. A passing result does not confirm ownership, and a risk indicator is not a finding of fraud.',
        ],

        'checks' => [
            'record_identity_consistency' => 'Record identity and location consistency',
            'deed_record_consistency' => 'Submitted deed reference consistency',
            'ownership_share_consistency' => 'Claimed ownership and share consistency',
            'ownership_chain_evidence' => 'Available ownership-chain evidence',
            'mutation_status' => 'Mutation status',
            'lien_mortgage_status' => 'Lien or mortgage position',
            'duplicate_sale_indicators' => 'Duplicate-sale and conflicting-claim indicators',
            'court_dispute_status' => 'Court or dispute information',
        ],
    ],

    // ================= BUYER INTEREST =================
    'interest' => [
        'title' => 'My Enquiries',
        'subtitle' => 'Listings you have enquired about',
        'received_title' => 'Buyer Interest Requests',
        'received_subtitle' => 'Enquiries received against your listings',

        'empty' => 'You have not enquired about any listing.',
        'empty_hint' => 'When you find a listing you are interested in, you can register your interest here.',
        'received_empty' => 'No buyer has enquired about your listings yet.',

        'express' => 'Express interest',
        'field_message' => 'Message to the owner (optional)',
        'submit' => 'Send enquiry',

        'reference' => 'Reference',
        'listing' => 'Listing',
        'buyer' => 'Buyer',
        'status_label' => 'Status',
        'raised_on' => 'Raised on',
        'responded_on' => 'Responded on',
        'your_message' => 'Your message',
        'seller_response' => 'Owner response',
        'contact_shared' => 'Contact details shared',

        'respond' => 'Respond',
        'field_response' => 'Your response',
        'field_status' => 'Set status',
        'field_contact_shared' => 'Share my contact details with this buyer',
        'submit_response' => 'Send response',

        'submitted' => 'Enquiry sent. This does not create any contract or reservation.',
        'responded' => 'Response recorded.',
        'already_raised' => 'You have already raised an enquiry for this listing.',
        'cannot_enquire_own_listing' => 'You cannot raise an enquiry against your own listing.',

        'not_transfer_warning' => 'An enquiry, an acknowledgement and even a proposed site visit create no legal obligation and transfer nothing. Ownership only changes through the official registration process, which is not part of this system.',
        'privacy_note' => 'Only you and the listing owner can see this enquiry. Your contact details are not shared unless the owner chooses to.',
    ],

    // ================= REVIEW WORKFLOW =================
    'review' => [
        'not_implemented_title' => 'Reviewer interface not implemented',
        'not_implemented_body' => 'The structures for review exist and are enforced by policy, but the registry officer review screens are not built in this version. Submissions will remain in their pending state until that interface is implemented.',
        'separation_note' => 'A person cannot review their own submission. This is enforced server-side, not by the interface.',
    ],
];
